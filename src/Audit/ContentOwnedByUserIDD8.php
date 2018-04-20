<?php

namespace Drutiny\Plugin\Drupal8\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Annotation\Param;

/**
 * Content Owned By Drupal's Anonymous User
 * @Param(
 *  name = "uid",
 *  description = "UID to check content ownership against.",
 *  type = "integer"
 * )
*/
class ContentOwnedByUserIDD8 extends Audit {

  /**
   * @inheritdoc
   */
  public function audit(Sandbox $sandbox) {
    $uid = $sandbox->getParameter('uid', 0);
    $sandbox->setParameter('UID', $uid);

    $output = $sandbox->drush()->evaluate(function ($uid) {
      return count(\Drupal::entityQuery("node")->condition("uid", $uid)->execute());
    }, ['uid' => $uid]);

    if (empty($output)) {
      return TRUE;
    }

    // Set the value for total nodes
    $sandbox->setParameter('totalnodes', $output);

    return Audit::FAIL;
  }

}
