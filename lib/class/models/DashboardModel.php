<?php
  namespace models;
  use \data\entities;
  
  class DashboardModel {
    private $_statistics;
    private $_favourites;
    
    public function __construct() {
      $account =& \auth\Credentials::current()->account();
      
      $this->_translations = entities\Translation::getByAccount($account);
      $this->_favourites   = entities\Favourite::getByAccount($account);
    }
    
    public function getTranslations() {
      return $this->_translations;
    } 
    
    public function getFavourites() {
      return $this->_favourites;
    }
  }
?>
