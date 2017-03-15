<?php

/** $Id$
 * UserModel.php
 * @version 1.0.0, $Revision$
 * @package eroticam.pl
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Model\User {
    
    class UserModel extends \Core\Model\AbstractModel {

        const STATUS_INACTIVE = 'inactive';
        const STATUS_NEW = 'new';
        const STATUS_ACTIVE = 'active';
        const STATUS_BLOCKED = 'blocked';
        const STATUS_DELETED = 'deleted';

        protected $id;
        protected $firstname;
        protected $lastname;
        protected $username;
        protected $facebook;
        protected $password;
        protected $email;
        protected $status;
        protected $auth;
        protected $lang;
        protected $gender;
        protected $settings;
        protected $theme;
        protected $birthday;
        protected $created;
        protected $logged;

        public function getId() {
            return (int)$this->id;
        }

        public function getFirstname() {
            return $this->firstname;
        }

        public function getLastname() {
            return $this->lastname;
        }

        public function getUsername() {
            return $this->username;
        }

        public function getPassword() {
            return $this->password;
        }

        public function getEmail() {
            return $this->email;
        }

        public function getStatus() {
            return $this->status;
        }

        public function getAuth() {
            return $this->auth;
        }

        public function getLang() {
            return $this->lang;
        }

        public function getGender() {
            return $this->gender;
        }

        public function getSettings() {
            return $this->settings;
        }

        public function getTheme() {
            return $this->theme;
        }

        public function getBirthday() {
            return $this->birthday;
        }

        public function getCreated() {
            return $this->created;
        }

        public function getLogged() {
            return $this->logged;
        }

        public function setId($id) {
            $this->id = (int)$id;
        }

        public function setFirstname($firstname) {
            $this->firstname = $firstname;
        }

        public function setLastname($lastname) {
            $this->lastname = $lastname;
        }

        public function setUserName($username) {
            $this->username = $username;
        }

        public function setPassword($password) {
            $this->password = $password;
        }

        public function setEmail($email) {
            $this->email = $email;
        }

        public function setStatus($status) {
            $this->status = $status;
        }

        public function setAuth($auth) {
            $this->auth = $auth;
        }

        public function setLang($lang) {
            $this->lang = $lang;
        }

        public function setGender($gender) {
            $this->gender = $gender;
        }

        public function setSettings($settings) {
            $this->settings = $settings;
        }

        public function setTheme($theme) {
            $this->theme = $theme;
        }

        public function setBirthday($birthday) {
            $this->birthday = $birthday;
        }

        public function setCreated($created) {
            $this->created = $created;
        }

        public function setLogged($logged) {
            $this->logged = $logged;
        }

        public function calculateHash() {
            $hash = sha1($this->getEmail().$this->getUsername().$this->getBirthday().$this->getCreated());

            return $hash;
        }

        public function calculateUserAge() {
            $birthdayDate = new \DateTime($this->getBirthday());
            $currentDate  = new \DateTime('00:00:00');

            $diff = $currentDate->diff($birthdayDate);
            return (int)$diff->y;
        }

        public function isActive() {
            return ($this->status == 'new' || $this->status == 'active')?true:false;
        }

        public function isNew() {
            return ($this->status == 'new')?true:false;
        }
            
    }
    
}