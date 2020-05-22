<?php

namespace Drutiny\Plugin\Drupal8\Audit;

use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;

/**
 * @Param(
 *  name = "expression",
 *  type = "string",
 *  default = "true",
 *  description = "The expression language to evaludate. See https://symfony.com/doc/current/components/expression_language/syntax.html"
 * )
 * @Param(
 *  name = "bin_backends",
 *  description = "Key value pair of cache bins and expected bin backend where key is bin and value is backend",
 *  type = "array"
 * )
 */
class CacheBinBackend extends AbstractAnalysis {

  /**
   * {@inheritdoc}
   */
  protected function gather(Sandbox $sandbox) {
    // Get site's cache bin backend and all settings.
    list($cache_bin_backends, $site_settings) = $sandbox->drush()->evaluate(function () {
      return [
        \Drupal::getContainer()->getParameter('cache_default_bin_backends'),
        \Drupal\Core\Site\Settings::getAll(),
      ];
    });

    if (!empty($site_settings['cache']['bins'])) {
      // Merge cache backend from default and override from settings.
      $cache_bin_backends = array_merge($cache_bin_backends, $site_settings['cache']['bins']);
    }

    $bin_backends = $sandbox->getParameter('bin_backends', []);

    $result = [];
    foreach ($bin_backends as $bin_backend) {
      $bin = key($bin_backend);
      if (isset($cache_bin_backends[$bin])
        && $bin_backend[$bin] != $cache_bin_backends[$bin]) {
        $result[] = [
          'bin' => $bin,
          'current_backend' => $cache_bin_backends[$bin],
          'expected_backend' => $bin_backend[$bin],
        ];
      }
    }

    $sandbox->setParameter('cache_bins', $result);
    $sandbox->setParameter('site_settings', $site_settings);
  }

}
