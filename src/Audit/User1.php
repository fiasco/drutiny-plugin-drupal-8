<?php

namespace Drutiny\Plugin\Drupal8\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\RemediableInterface;
use Drutiny\Annotation\Param;

/**
 * User #1
 * @Param(
 *  name = "email",
 *  description = "The email the user account should be.",
 * )
 * @Param(
 *  name = "blacklist",
 *  description = "List of usernames that are not acceptable.",
 * )
 * @Param(
 *  name = "status",
 *  description = "Whether the account should be enabled or disabled.",
 * )
 */
class User1 extends Audit implements RemediableInterface {

  /**
   *
   */
  public function audit(Sandbox $sandbox) {
    // Get the details for user #1.
    $user = $sandbox->drush(['format' => 'json'])
                    ->userInformation(1);

    $user = (object) array_pop($user);

    $errors = [];
    $fixups = [];

    // Username.
    $pattern = $sandbox->getParameter('blacklist');
    if (preg_match("#${pattern}#i", $user->name)) {
      $errors[] = "Username '$user->name' is too easy to guess.";
    }
    $sandbox->setParameter('username', $user->name);

    // Email address.
    $email = $sandbox->getParameter('email');

    if (!empty($email) && ($email !== $user->mail)) {
      $errors[] = "Email address '$user->mail' is not set correctly.";
    }

    // Status.
    $status = (bool) $sandbox->getParameter('status');
    if ($status !== (bool) $user->status) {
      $errors[] = 'Status is not set correctly. Should be ' . ($user->status ? 'active' : 'inactive') . '.';
    }

    $sandbox->setParameter('errors', $errors);
    return empty($errors) ? TRUE : Audit::WARNING;
  }

  public function remediate(Sandbox $sandbox)
  {

    // Get the details for user #1.
    $user = $sandbox->drush(['format' => 'json'])
                    ->userInformation(1);

    $user = (object) array_pop($user);

    $output = $sandbox->drush()->evaluate(function ($uid, $status, $password, $email, $username) {
      $user =  \Drupal\user\Entity\User::load($uid);
      if ($status) {
        $user->activate();
      }
      else {
        $user->block();
      }
      $user->setPassword($password);
      $user->setEmail($email);
      $user->setUsername($username);
      $user->set('init', $email);
      $user->save();
      return TRUE;
    }, [
      'uid' => $user->uid,
      'status' => (int) (bool) $sandbox->getParameter('status'),
      'password' => $this->generateRandomString(),
      'email' => $sandbox->getParameter('email'),
      'username' => $this->generateRandomString()
    ]);

    return $this->audit($sandbox);
  }

  /**
   * Generate a random string.
   *
   * @param int $length
   *   [optional]
   *   the length of the random string.
   *
   * @return string
   *   the random string.
   */
  public function generateRandomString($length = 32) {

    // Generate a lot of random characters.
    $state = bin2hex(random_bytes($length * 2));

    // Remove non-alphanumeric characters.
    $state = preg_replace("/[^a-zA-Z0-9]/", '', $state);

    // Trim it down.
    return substr($state, 0, $length);
  }

}
