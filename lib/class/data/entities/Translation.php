<?php
  namespace data\entities;
  
  class Translation extends OwnableEntity {
  
    // Mutable columns
    public $word;
    public $translation;
    public $etymology;
    public $type;
    public $source;
    public $comments;
    public $tengwar;
    public $gender;
    public $phonetic;
    public $language;
    public $senseID;
    public $group;
    public $externalID;
    public $uncertain;
    
    // Semi-mutable column
    public $index;
    public $rating;
    
    // Read-only columns
    public $id;
    public $wordID;
    public $dateCreated;
    public $authorID;
    public $authorName;
    public $latest;

    // Static comntainer for type lists. This is meant to speed up bulk loading by reducing
    // database access.
    private static $availableTypes = null;
    
    public static function countByAccount(Account &$account) {
      $db = \data\Database::instance()->connection();
      $query = null;
      try {
        $query = $db->prepare(
          'SELECT COUNT(*) AS `count` FROM `translation` t 
             WHERE t.`AuthorID` = ? AND t.`Latest` = \'1\' AND t.`Deleted` = b\'0\''
        );
        $query->bind_param('i', $account->id);
        $query->execute();
        $query->bind_result($count);
        
        if ($query->fetch()) {
          return $count;
        }
      } finally {
        if ($query instanceof \mysqli_stmt) {
          $query->close();
        }
      }
      
      return 0;
    }
  
    /**
     * Retrieves an array with translations associated with the specified account.
     * @param Account $account
     * @return array of \data\entities\Translation
     */
    public static function getByAccount(Account &$account, $offset = -1, $max = -1) {
      $db = \data\Database::instance()->connection();
      $translations = array();
      
      $query = null;
      try {
        $query =  $db->prepare(
         \data\SqlHelper::paginate(
           'SELECT t.`TranslationID`, w.`Key`, t.`LanguageID`, t.`Translation`, t.`DateCreated`,
                   tg.`TranslationGroupID`, tg.`Name` AS `TranslationGroup`, tg.`Canon`
            FROM `translation` t
              INNER JOIN `word` w ON w.`KeyID` = t.`WordID`
              INNER JOIN `language` l ON l.`ID` = t.`LanguageID`
              LEFT JOIN `translation_group` tg ON tg.`TranslationGroupID` = t.`TranslationGroupID`
            WHERE t.`AuthorID` = ? AND t.`Latest` = \'1\' AND t.`Index` = \'0\' AND l.`Invented` = \'1\'
                  AND t.`Deleted` = b\'0\'
            ORDER BY t.`DateCreated` DESC', $offset, $max
         )
        );
        $query->bind_param('i', $account->id);
        $query->execute();
        $query->bind_result($id, $word, $language, $translation, $creationDate, $groupID, $groupName, $canon);

        while ($query->fetch()) {
          $translations[] = new Translation(array(
            'id'          => $id,
            'word'        => $word,
            'language'    => $language,
            'owner'       => $account->id,
            'translation' => $translation,
            'dateCreated' => new \DateTime($creationDate),
            'group'       => new TranslationGroup(array('id' => $groupID, 'name' => $groupName, 'canon' => $canon))
          ));
        }
      } finally {
        if ($query !== null) { 
          $query->close();
        }
      }
      
      return $translations;
    }

    public static function getTypes() {
      if (is_array(self::$availableTypes)) {
        return self::$availableTypes;
      }
    
      $db = \data\Database::instance();
    
      $data = array();
      $query = $db->connection()->query(
          "SHOW COLUMNS FROM `translation` WHERE `Field` = 'Type'"
      );
    
      while ($row = $query->fetch_object()) {
        $values = null;
        if (preg_match_all('/\'([a-zA-Z\\/\\|]+)\'/', $row->Type, $values)) {
          foreach ($values[1] as $value) {
            $data[$value] = str_replace(array('/', '|'), array('. and ', '. or '), $value).'.';
          }
    
          ksort($data);
        }
      }
    
      $query->close();
    
      // Save the results, for quicker access next time.
      self::$availableTypes = $data;
      return $data;
    }
    
    public static function translateSingle($id) {
      $db = \data\Database::instance();
      
      $translation = new \data\entities\Translation();
      $translation->load($id);
      
      // Does it really exist?
      if (! $translation->validate()) {
        return null;
      }
      
      // Don't allow people to find indexes
      if ($translation->index) {
        return null;
      }
    
      // prepare for the web
      $translation->transformContent();
    
      // retrieve the sense 
      $sense = new Sense();
      $sense->load($translation->senseID);
      
      if (! $sense->validate()) {
        return null;
      }
      
      // load language
      $language = new Language();
      $language->load($translation->language);
      
      if (! $language->validate()) {
        return null;
      }
      
      // emulate the output from the multiple-yield translation method
      $data = array(
          'senses'         => array($sense->id => $sense->identifier),
          'translations'   => array($language->name => array($translation)),
          'keywordIndexes' => array(),
          'translation'    => $translation
      );
      
      return $data;
    }
    
    public static function translate($term, $languageFilter = null) {
      $db             = \data\Database::instance();
      $normalizedTerm = \utils\StringWizard::normalize($term);
    
      $senses   = self::findSensesByTerm($normalizedTerm);
      $data     = array('senses' => $senses);
      $senseIDs = array_keys($senses);
    
      if (count($senseIDs) < 1) {
        return null;
      }
    
      $senseIDs = implode(',', $senseIDs);
    
      // Find all translations for the words specified. The array of IDs is used
      // now as a means to identify the words themselves.
      $query = $db->connection()->prepare(
          'SELECT w.`Key` AS `Word`, t.`TranslationID`, t.`Translation`, t.`Etymology`,
           t.`Type`, t.`Source`, t.`Comments`, t.`Tengwar`, t.`Phonetic`,
           l.`Name` AS `Language`, t.`NamespaceID`, l.`Invented` AS `LanguageInvented`,
           t.`AuthorID`, a.`Nickname`, w.`NormalizedKey`, t.`Index`,
           t.`DateCreated`, tg.`TranslationGroupID`, tg.`Name` AS `TranslationGroup`,
           tg.`Canon`, t.`Uncertain`
         FROM `translation` t
         INNER JOIN `word` w ON w.`KeyID` = t.`WordID`
         INNER JOIN `language` l ON l.`ID` = t.`LanguageID`
         LEFT JOIN `auth_accounts` a ON a.`AccountID` = t.`AuthorID`
         LEFT JOIN `translation_group` tg ON tg.`TranslationGroupID` = t.`TranslationGroupID`
         WHERE t.`NamespaceID` IN('.$senseIDs.') AND t.`Latest` = 1 AND t.`Deleted` = b\'0\'
         ORDER BY l.`Order` ASC, t.`NamespaceID` ASC, l.`Name`
         DESC, w.`Key` ASC'
      );
    
      $query->execute();
      $query->bind_result(
          $word, $translationID, $translation, $etymology, $type, $source, $comments, $tengwar,
          $phonetic, $language, $senseID, $inventedLanguage, $authorID, $authorName,
          $normalizedWord, $isIndex, $dateCreated, $groupID, $groupName, $canon, $uncertain
      );
    
      $data['translations']   = array();
      $data['keywordIndexes'] = array();
    
      while ($query->fetch()) {
    
        if ($isIndex == 1) {
    
          $ptr =& $data['keywordIndexes'];
    
        } else {
    
          if (!isset($data['translations'][$language]))
            $data['translations'][$language] = array();
    
          $ptr =& $data['translations'][$language];
        }
    
        // Order affected associative array by language
        $translation = new Translation(
            array(
                'word'        => $word,
                'id'          => $translationID,
                'translation' => \utils\StringWizard::createLinks($translation),
                'etymology'   => \utils\StringWizard::createLinks($etymology),
                'type'        => $type,
                'tengwar'     => \utils\StringWizard::preventXSS($tengwar),
                'phonetic'    => \utils\StringWizard::preventXSS($phonetic),
                'source'      => \utils\StringWizard::preventXSS($source),
                'comments'    => empty($comments) ? null : \utils\StringWizard::createLinks($comments),
                'language'    => $language,
                'senseID'     => $senseID,
                'authorID'    => $authorID,
                'authorName'  => $authorName,
                'dateCreated' => $dateCreated,
                'group'       => new TranslationGroup(array('id' => $groupID, 'name' => $groupName, 'canon' => $canon)),
                'uncertain'   => $uncertain
            )
        );
    
        self::calculateRating($translation, $normalizedTerm);
    
        $ptr[] = $translation;
      }
    
      $query->close();
    
      foreach (array_keys($data['translations']) as $language)
        usort($data['translations'][$language], '\\utils\\TranslationComparer::compare');
    
      return $data;
    }
    
    public function __construct($data = null) {
      $this->id = 0;
      $this->senseID = 0;
      $this->type = 'unset';
      $this->gender = 'none';
      $this->word = null;
      $this->index = false;
      $this->externalID = null;
      $this->group = TranslationGroup::emptyGroup();
      $this->uncertain = false;

      parent::__construct($data);
    }
    
    public function validate() {
      if (preg_match('/^\\s*$/', $this->word) || 
          preg_match('/^\\s*$/', $this->translation) || 
          (!$this->index && $this->language == 0) || 
           $this->senseID == 0) {
        return false;
      }
      
      return true;
    }

    /**
     * Saves the translation to the database. The translation entry is associated with the current user.
     * @return $this|Translation|TranslationReview
     * @throws \ErrorException
     * @throws \exceptions\InvalidParameterException
     */
    public function save() {
      if (!$this->validate()) {
        throw new \exceptions\InvalidParameterException('translation');
      }

      // Request permission to save the changes to the translation entry
      $request = new \auth\TranslationAccessRequest($this->id);
      $credentials =& \auth\Credentials::request($request);

      return $this->saveInternal($credentials);
    }

    /**
     * Transfers the translation to the specified user.
     * @param \auth\Credentials $credentials
     * @return Translation
     * @throws \ErrorException
     * @throws \exceptions\InvalidParameterException
     */
    public function transfer(\auth\Credentials $credentials) {
      return $this->saveInternal($credentials);
    }

    /**
     * Saves the translation to the translation table.
     * @param \auth\Credentials $credentials
     * @return $this
     * @throws \ErrorException
     * @throws \exceptions\InvalidParameterException
     */
    private function saveInternal(\auth\Credentials $credentials) {
      if ($this->externalID !== null && empty($this->externalID)) {
        $this->externalID = null;
      }

      // Create or load the word associated with this translation.
      $word = new \data\entities\Word();
      $word->create($this->word);
      
      // Acquire a connection for making changes in the database.
      $db = \data\Database::instance()->connection();
      
      // check sense validity
      $sense = new \data\entities\Sense();
      if ($sense->load($this->senseID) === null) {
        throw new \exceptions\InvalidParameterException('senseID');
      }
      
      if ($this->index && $this->loadIndex($sense, $word)) {
        return $this;
      }

      // Acquire current author
      $accountID = $credentials->account()->id; // this is only necessary for the MySQLi
      $eldestTranslationID = null;

      // Deprecate current translation entry
      if ($this->id > 0) {
        // Indexes doesn't use words, hence this functionality applies only
        // to translations.
        $query = $db->prepare('SELECT `WordID`, `NamespaceID`, `EldestTranslationID` FROM `translation` WHERE `TranslationID` = ?');
        $query->bind_param('i', $this->id);
        $query->execute();
        $query->bind_result($currentWordID, $currentSenseID, $eldestTranslationID);
        $query->fetch();
        $query->free_result();
        $query = null;
        
        if (! $eldestTranslationID) {
          $eldestTranslationID = $this->id;
        }

        // ExternalID is unique, so deprecate previous row with the specified ID by setting it to NULL.
        if (null !== $this->externalID) {
          $query = $db->prepare('UPDATE `translation` SET `ExternalID` = NULL WHERE `TranslationID` = ?');
          $query->bind_param('i', $this->id);
          $query->execute();
          $query = null;
        }
      
        // remove all keywords to the (now) deprecated translation entry - the keywords table
        // shall only contain current, up-to-date definitions.
        $query = $db->prepare('DELETE FROM `keywords` WHERE `TranslationID` = ?');
        $query->bind_param('i', $this->id);
        $query->execute();
        $query = null;
      
        // deassociate the word with the previous translation entry
        if ($currentWordID != $word->id) {
          \data\entities\Word::unregisterReference($currentWordID);
        }
      
        // deassociate the sense with the previous translation entry
        if ($currentSenseID != $this->senseID) {
          $query = $db->prepare('SELECT COUNT(*) FROM `translation` WHERE `Latest` = 1 AND `NamespaceID` = ?');
          $query->bind_param('i', $currentSenseID);
          $query->execute();
          $query->bind_result($references);
          $query->fetch();
          $query->free_result();
          $query = null;
      
          // If there are no references, delete the sense from active keywords table
          if ($references < 1) {
            $query = $db->prepare('DELETE FROM `keywords` WHERE `NamespaceID` = ?');
            $query->bind_param('i', $currentSenseID);
            $query->execute();
            $query = null;
          }
        }
      }
      
      if ($accountID < 1) {
        throw new \ErrorException('Invalid log in state.');
      }
      
      // Insert the row
      $query = $db->prepare(
          "INSERT INTO `translation` (`TranslationGroupID`, `Translation`, `Etymology`, `Type`, `Source`, `Comments`,
        `Tengwar`, `Phonetic`, `LanguageID`, `WordID`, `NamespaceID`, `Index`, `AuthorID`, `EldestTranslationID`,
        `ExternalID`, `Uncertain`, `Latest`, `DateCreated`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '1', NOW())"
      );
      $query->bind_param('isssssssiiiiiisi',
        $this->group->id, $this->translation, $this->etymology, $this->type, $this->source, $this->comments,
        $this->tengwar, $this->phonetic, $this->language, $word->id, $this->senseID, $this->index, $accountID,
        $eldestTranslationID, $this->externalID, $this->uncertain
      );
      
      $query->execute();
      
      $previousTranslationId = $this->id;
      $this->id = $query->insert_id;

      $query = null;
      
      if ($this->id == 0) {
        // failed!
        throw new \ErrorException('Failed to create translation entry for '.$this->word.'. Failure '.$db->errno.': '.$db->error);
      }
      
      // update the keywords table with the new results - but in order to do this, we'll need to normalize
      // the input strings to make sure to avoid collisions
      $nword    = \utils\StringWizard::normalize($word->key);
      $nkeyword = \utils\StringWizard::normalize($this->translation);
      
      // insert reference
      $insert = array('key' => $word->key, 'nkey' => $nword, 'transID' => $this->id, 'wordID' => $word->id);
      
      // The word key is always associated with this translation entry
      $query = $db->prepare('INSERT INTO `keywords` (`Keyword`, `NormalizedKeyword`, `TranslationID`, `WordID`) VALUES(?,?,?,?)');
      $query->bind_param('ssii', $insert['key'], $insert['nkey'], $insert['transID'], $insert['wordID']);
      $query->execute();
      
      // The translation field might contain information interesting in regards to its relevance. If this information
      // is not equal to the word already associated with the new entry, add the it as well.
      if ($nword !== $nkeyword && !preg_match('/^\\s*$/', $nkeyword)) {
        $keywordObj = new \data\entities\Word();
        $keywordObj->create($this->translation);
      
        $insert['key']     = $this->translation;
        $insert['nkey']    = $nkeyword;
        $insert['transID'] = $this->id;
        $insert['wordID']  = $keywordObj->id;
      
        $query->bind_param('ssii', $insert['key'], $insert['nkey'], $insert['transID'], $insert['wordID']);
        $query->execute();
      }
      
      $query = null;
      
      // Deprecated previous translation
      if ($previousTranslationId > 0) {
        $query = $db->prepare('UPDATE `translation` SET `Latest` = \'0\', `ParentTranslationID` = ? WHERE `TranslationID` = ?');
        $query->bind_param('ii', $this->id, $previousTranslationId);
        $query->execute();
        $query = null;
      }
      
      Sentence::updateReference($previousTranslationId, $this);
      return $this;
    }

    public function saveIndex() {
      $this->translation = 'index';
      $this->index = true;
      $this->save();
      
      return $this;
    }
    
    public function remove() {
      if ($this->id < 1) {
        throw new \exceptions\MissingParameterException('id');
      }

      $conn = \data\Database::instance()->connection();

      $stmt = $conn->prepare('DELETE FROM `keywords` WHERE `TranslationID` = ?');
      $stmt->bind_param('i', $this->id);
      $stmt->execute();

      // Mark translation entries as deleted.
      $stmt = $conn->prepare('UPDATE `translation` SET `Deleted` = b\'1\' WHERE `TranslationID` = ? AND `Index` = \'0\'');
      $stmt->bind_param('i', $this->id);
      $stmt->execute();

      // Delete indexes permanently.
      $stmt = $conn->prepare('DELETE FROM `translation` WHERE `TranslationID` = ? AND `Index` = \'1\'');
      $stmt->bind_param('i', $this->id);
      $stmt->execute();
    }
    
    public function load($id = null) {
      // result container
      if ($id === null) {
        $id = $this->id;
      }
      
      if ($id < 1) {
        throw new \exceptions\InvalidParameterException('id');
      }
      
      $db = \data\Database::instance();
      $query = $db->connection()->prepare(
        'SELECT 
          t.`LanguageID`, t.`Translation`, t.`Etymology`, t.`Type`, t.`Source`, t.`Comments`, 
          t.`Tengwar`, t.`Gender`, t.`Phonetic`, w.`Key`, t.`NamespaceID`, t.`AuthorID`,
          t.`DateCreated`, t.`Latest`, t.`Index`, t.`WordID`, a.`Nickname`,
          tg.`TranslationGroupID`, tg.`Name` AS `TranslationGroup`, tg.`Canon`, t.`Uncertain`
         FROM `translation` t 
           LEFT JOIN `word` w ON w.`KeyID` = t.`WordID`
           LEFT JOIN `translation_group` tg ON tg.`TranslationGroupID` = t.`TranslationGroupID`
           INNER JOIN `auth_accounts` a ON a.`AccountID` = t.`AuthorID`
         WHERE t.`TranslationID` = ?'
      );

      $query->bind_param('i', $id);
      $query->execute();
      $query->bind_result(
        $this->language, $this->translation, $this->etymology, $this->type, $this->source, $this->comments,
        $this->tengwar, $this->gender, $this->phonetic, $this->word, $this->senseID, $this->authorID,
        $this->dateCreated, $this->latest, $this->index, $this->wordID, $this->authorName,
        $groupID, $groupName, $canon, $this->uncertain
      );
      
      if ($query->fetch()) {
        $this->id = $id;
        $this->group = new TranslationGroup(array('id' => $groupID, 'name' => $groupName, 'canon' => $canon));
      }
      
      $query->free_result();
      $query = null;
    }

    public function loadIDForExternalID() {
      if (empty($this->externalID)) {
        return;
      }

      $query = \data\Database::instance()->connection()->prepare(
        'SELECT `TranslationID`, `NamespaceID` FROM `translation` WHERE `ExternalID` = ?'
      );

      try {
        $query->bind_param('s', $this->externalID);
        $query->execute();
        $query->bind_result($this->id, $this->senseID);
        if ($query->fetch()) {
          return true;
        }
      } finally {
        $query->free_result();
        $query = null;
      }

      return false;
    }
    
    /**
     * Attempts to load the index for the specified sense and word. Assigns $id, and returns true on success.
     * @param Sense $sense
     * @param Word $word
     * @throws \exceptions\InvalidParameterException
     * @return boolean
     */
    public function loadIndex(Sense &$sense, Word &$word) {

      if ($sense == null || $sense->id == 0) {
        throw new \exceptions\InvalidParameterException('sense');
      }
      
      if ($word == null || $word->id == 0) {
        throw new \exceptions\InvalidParameterException('word');
      }
      
      $db = \data\Database::instance()->connection();
      $query = null;
      $translationID = 0;
      
      try {
        $query = $db->prepare('SELECT `TranslationID` FROM `translation`
                               WHERE `Latest` = \'1\' AND `Index` = \'1\' AND `WordID` = ? AND `NamespaceID` = ?');
        $query->bind_param('ii', $word->id, $sense->id);
        $query->execute();
        $query->bind_result($translationID);
        $query->fetch();
      } finally {
        $query->close();
      }
      
      if ($translationID != 0) {
        $this->id = $translationID;
        $this->index = true; 
        return true;
      }
      
      return false;
    }
    
    /**
     * Retrieves an array of all indexes associated with the senses this translation is tied to.
     * Every index is represented by an associative array, with the elements ID, wordID and word.  
     * @return array
     */
    public function getIndexes() {
      $sense = new Sense(array('id' => $this->senseID));
      $indexes = $sense->getIndexes();

      $items = array();
      foreach ($indexes as $index) {
        $items[] = array(
          'ID'     => $index->id,
          'wordID' => $index->wordID,
          'word'   => $index->word
        );
      }

      return $items;
    }
    
    /**
     * Transforms all longer strings into their HTML equivalents.
     */
    public function transformContent() {
      $this->translation = \utils\StringWizard::createLinks($this->translation);
      $this->comments    = \utils\StringWizard::createLinks($this->comments);
    }
    
    /**
     * Disassociates this instance of the translation from its current owner. No changes are committed to the database.
     */
    public function disassociate() {
      $this->id = 0;
      $this->authorID = 0;
      $this->authorName = null;
      $this->dateCreated = null;
    }
    
    private static function findSensesByTerm($normalizedTerm) {
      $senses = array();
      
      // Attempt to find the senses associated with the word. This might yield multiple
      // IDs, so these will be put in an array.
      $db    = \data\Database::instance();
      $query = $db->connection()->prepare(
        'SELECT DISTINCT k.`NamespaceID`, k.`Keyword`
           FROM `keywords` k
           WHERE k.`NormalizedKeyword` = ? AND k.`NamespaceID` IS NOT NULL
           UNION (
            SELECT t.`NamespaceID` , k.`Keyword`
              FROM `keywords` k
                INNER JOIN `translation` t ON t.`TranslationID` = k.`TranslationID`
              WHERE k.`TranslationID` IS NOT NULL AND k.`NormalizedKeyword` = ?
          )'
      );
      
      $query->bind_param('ss', $normalizedTerm, $normalizedTerm);
      $query->execute();
      $query->bind_result($senseID, $identifier);
      
      while ($query->fetch()) {
        $senses[$senseID] = $identifier;
      }
      
      $query->close();
      return $senses;
    }
    
    private static function calculateRating(Translation & $translation, $term) {
      $rating = 0;
      
      // First, check if the gloss contains the search term by looking for its
      // position within the word property, albeit normalized.
      $n = \utils\StringWizard::normalize($translation->word);
      $pos = strpos($n, $term);
      
      if ($pos !== false) {
        // The "cleaner" the match, the better
        $rating = 100000 + ($pos * -1) * 10;
        
        if ($pos === 0 && $n == $term) {
          $rating *= 2;
        }
      }
      
      // If the previous check failed, check for the translations field. Statistically,
      // this is the most common case.
      if ($rating === 0) {
        $n = \utils\StringWizard::normalize($translation->translation);
        $pos = strpos($n, $term);
        
        if ($pos !== false) {
          $rating = 10000 + ($pos * -1) * 10;
          
          if ($pos === 0 && $n == $term) {
            $rating *= 2;
          }
        }
      }
      
      // If the previous check failed, check within the comments field. Statistically,
      // this is an uncommon match.
      if ($rating === 0 && $translation->comments !== null) {
        $n = \utils\StringWizard::normalize($translation->comments);
        $pos = strpos($n, $term);
        
        if ($pos !== false) {
          $rating = 1000;
        }
      }
      
      // Default rating for all other cases, probably matches by keyword.
      if ($rating === 0) {
        $rating = 100;
      }
      
      // Bump all unverified translations to a trailing position
      if (! $translation->group->canon) {
        $rating = -110000 + $rating;
      }
      
      $translation->rating = $rating;
    }
  }
