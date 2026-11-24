<?php

/**
 * @file
 * Generate/refresh topic embeddings using the configured pinned model.
 */

echo json_encode(\Drupal::service('med_search.search')->indexTopics(), JSON_PRETTY_PRINT) . "\n";
