<?php

namespace Drutiny\Plugin\Drupal8\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\RemediableInterface;
use Drutiny\Driver\DrushFormatException;
use Drutiny\Annotation\Param;

/**
 * Generic module is disabled check.
 * @Param(
 *  name = "module",
 *  description = "The module to check is enabled.",
 *  type = "string"
 * )
 */
class ModuleDisabled extends Audit implements RemediableInterface {

  /**
   *
   */
  public function audit(Sandbox $sandbox)
  {

    $module = $sandbox->getParameter('module');

    try {
      $info = $sandbox->drush(['format' => 'json'])->pmList();
    }
    catch (DrushFormatException $e) {
      return strpos($e->getOutput(), $module . ' was not found.') !== FALSE;
    }

    if (!isset($info[$module])) {
      return TRUE;
    }

    $status = strtolower($info[$module]['status']);

    return ($status != 'enabled');
  }

  public function remediate(Sandbox $sandbox)
  {
    $module = $sandbox->getParameter('module');
    $sandbox->drush()->pmUninstall($module, '-y');
    return $this->audit($sandbox);
  }

}
