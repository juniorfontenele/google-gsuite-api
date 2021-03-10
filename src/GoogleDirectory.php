<?php

namespace JuniorFontenele\Google\GSuite;

use Exception;
use Google\Client;
use Google_Service_Directory;
use Google_Service_Directory_User;
use Google_Service_Directory_UserName;
use JuniorFontenele\Google\GSuite\Exceptions\GoogleApiException;

class GoogleDirectory {

  protected $client;

  public function __construct(string $credentialsFile, string $impersonateAccount, string $appName = 'Google GSuite Api') {
    putenv('GOOGLE_APPLICATION_CREDENTIALS='.$credentialsFile);

    $client = new Client();
    $client->setApplicationName($appName);
    $client->useApplicationDefaultCredentials();
    $client->addScope(Google_Service_Directory::ADMIN_DIRECTORY_GROUP);
    $client->addScope(Google_Service_Directory::ADMIN_DIRECTORY_GROUP_MEMBER);
    $client->addScope(Google_Service_Directory::ADMIN_DIRECTORY_USER);
    $client->addScope(Google_Service_Directory::ADMIN_DIRECTORY_USER_ALIAS);
    $client->setSubject($impersonateAccount);

    $this->client = new Google_Service_Directory($client);
  }

  public function createUser(string $email, string $firstName, string $lastName, string $password, string $recoveryEmail = null, bool $changePasswordNextLogin = true) {
    $user = new Google_Service_Directory_User();
    $name = new Google_Service_Directory_UserName();
    $name->setGivenName($firstName);
    $name->setFamilyName($lastName);
    $name->setFullName($name->getGivenName() . ' ' . $name->getFamilyName());
    $user->setName($name);
    $user->setPrimaryEmail($email);
    $user->setPassword($password);
    if ($recoveryEmail) {
      $user->setRecoveryEmail($recoveryEmail);
    }
    $user->setChangePasswordAtNextLogin($changePasswordNextLogin);

    return $this->client->users->insert($user);
  }

  public function resetPassword(string $email, string $password, bool $changePasswordNextLogin = true): Google_Service_Directory_User {
    $user = $this->client->users->get($email);
    if (!$user) {
      throw new GoogleApiException('User not found');
    }
    $user->setPassword($password);
    $user->setChangePasswordAtNextLogin($changePasswordNextLogin);
    return $this->client->users->update($email, $user);
  }

  public function getUser(string $email): Google_Service_Directory_User {
    $user = $this->client->users->get($email);
    if (!$user) {
      throw new GoogleApiException('User not found');
    }
    return $user;    
  }

  public function accountExists(string $email): bool {
    try {
      $this->getUser($email);
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

}