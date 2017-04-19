update keywords set creationdate = now() where creationdate < '1970-01-01 00:00:00';

create table if not exists `sense` (
  `SenseID` int not null,
  `Description` text collate utf8_swedish_ci,
  primary key(`SenseID`)
);

-- Add foreign key to keywords and translation
alter table `keywords` add `SenseID` int null;
alter table `keywords` add `IsSense` bit not null default 0;
alter table `translation` add `SenseID` int null;
alter table `translation` add index `idx_senseID` (`SenseID`);

-- Trim whitespace
update `word` set 
  `Key` = trim(`Key`), 
  `NormalizedKey` = trim(`NormalizedKey`), 
  `ReversedNormalizedKey` = trim(`ReversedNormalizedKey`);

-- Create unique senses
insert into `sense` (`SenseID`, `Description`)
  select distinct `IdentifierID`, null 
  from `namespace` 
  order by `IdentifierID` asc;

-- Move deprecated namespaces to new senses
update `translation` as t 
  inner join `namespace` as n on n.`NamespaceID` = t.`NamespaceID`
  left join `namespace` as n0 on n0.`NamespaceID` = (
    select min(n1.`NamespaceID`)
      from `namespace` n1
      where n1.IdentifierID = n.IdentifierID
  )
  set t.`SenseID` = n0.IdentifierID;

-- Delete deprecated translations
delete t from `translation` as t 
  where t.`Latest` = 0 and not exists(select 1 from word where `KeyID` = t.`WordID`);

truncate `keywords`;

-- Transition from indexes to keywords
insert into `keywords` (`IsSense`, `SenseID`, `WordID`, `Keyword`, `NormalizedKeyword`, `ReversedNormalizedKeyword`)
  select distinct 
    0, n.`IdentifierID`, w.`KeyID`, w.`Key`, w.`NormalizedKey`, w.`ReversedNormalizedKey`
  from `translation` as t
    inner join `word` as w on w.`KeyID` = t.`WordID`
    inner join `namespace` as n on n.`NamespaceID` = t.`NamespaceID`
  where t.`Deleted` = 0 and t.`Index` = 1 and w.`KeyID` <> n.`IdentifierID`;

-- Add keywords to senses
insert into `keywords` (`IsSense`, `SenseID`, `WordID`, `Keyword`, `NormalizedKeyword`, `ReversedNormalizedKeyword`)
  select distinct
    1, s.`SenseID`, w.`KeyID`, w.`Key`, w.`NormalizedKey`, w.`ReversedNormalizedKey`
  from `sense` as s
    inner join `word` as w on w.`KeyID` = s.`SenseID`
  where exists (
    select 1 from `translation` as t where t.`Latest` = 1 and t.`Deleted` = 0 and t.`SenseID` = s.`SenseID` limit 1
  );

insert into `keywords` (`IsSense`, `SenseID`, `TranslationID`, `WordID`, `Keyword`, `NormalizedKeyword`, `ReversedNormalizedKeyword`)
  select distinct
    0, t.`SenseID`, t.`TranslationID`, t.`WordID`, w.`Key`, w.`NormalizedKey`, w.`ReversedNormalizedKey`
  from `translation` as t
    inner join `word` as w on w.`KeyID` = t.`WordID`
  where t.`Latest` = 1 and t.`Deleted` = 0 and not exists(
    select 1 from `keywords` as k where k.`SenseID` = t.`SenseID` and k.`Keyword` = w.`Key` and k.`WordID` = w.`KeyID` limit 1
  );

-- Transition existing definitions to markdown format
update translation set comments = replace(comments, '~', '**');
update translation set comments = replace(comments, '`', '**');

-- Transition providers from Hybridauth to Laravel Socialite
update `auth_providers` set `URL` = lower(`URL`);

-- Remember tokens are required by Laravel
alter table `auth_accounts` add `RememberToken` varchar(100)  collate utf8_swedish_ci null;

-- Adding neologisms to the sentence table
alter table `sentence` add `Neologism` bit default 0;
alter table `sentence` add `Approved` bit  default 0;
alter table `sentence` add `AuthorID` int null;
alter table `sentence` add `LongDescription` longtext null;
alter table `sentence` add `DateCreated` datetime default now();
alter table `sentence` add `Name` varchar(128) null;

update `sentence` as s
  set 
    s.`Approved` = 1, 
    s.`Name` = replace(replace(replace((
      select group_concat(f.`Fragment` separator ' ')
      from `sentence_fragment` as f
      where f.`SentenceID` = s.`SentenceID`
      group by f.`SentenceID`
    ), ' ,', ','), ' !', '!'), ' .', '.');

alter table `sentence` modify `Name` varchar(128) not null;

rename table `grammar_type` to `speech`;
alter table `speech` change `GrammarTypeID` `SpeechID` int;
replace into `speech` (`SpeechID`, `Name`, `Order`) values (99, 'Unknown', 99);
alter table `sentence_fragment` add `SpeechID` int null; -- Defaults to unset
alter table `sentence_fragment` add `InflectionID` int null; -- Defaults to unset (which is appropriate since it is only applicable to verbs)

alter table `inflection` drop `WordID`;
alter table `inflection` drop `TranslationID`;
alter table `inflection` drop `Comments`;
alter table `inflection` drop `Phonetic`;
alter table `inflection` drop `Source`;
alter table `inflection` drop `Mutation`;
alter table `inflection` change `GrammarTypeID` `SpeechID` int;
alter table `inflection` add `Name` varchar(32) not null;

insert into `version` (`number`, `date`) values (1.8, NOW());
