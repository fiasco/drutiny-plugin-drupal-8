<?php

namespace Drutiny\Plugin\Drupal8\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Driver\DrushFormatException;
use Drutiny\Annotation\Param;

/**
 *  Cron last run.
 * @Param(
 *  name = "cron_max_interval",
 *  type = "integer",
 *  description = "The maximum interval between "
 * )
 */
class CronHasRun extends Audit {

  /**
   *
   */
  public function audit(Sandbox $sandbox) {

    try {
      $timestamp = $sandbox->drush(['format' => 'json'])->stateGet('system.cron_last');
    }
    catch (DrushFormatException $e) {
      return FALSE;
    }

    // Check that cron was run in the last day.
    $since = time() - $timestamp['system.cron_last'];
    $sandbox->setParameter('cron_last', date('Y-m-d H:i:s', $timestamp['system.cron_last']));

    if ($since > $sandbox->getParameter('cron_max_interval')) {
      return FALSE;
    }

    return TRUE;
  }

}
