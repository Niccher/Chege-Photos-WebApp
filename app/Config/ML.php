<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class ML extends BaseConfig
{
    /**
     * Default InsightFace model pack: buffalo_l, buffalo_m, buffalo_s, buffalo_sc
     */
    public string $faceModelPack = 'buffalo_l';

    /**
     * Minimum detection confidence threshold (0.1 - 1.0)
     */
    public float $faceDetThresh = 0.5;

    /**
     * Sensitive attributes estimation (Age & Gender) enabled by default.
     */
    public bool $includeSensitive = true;

    /**
     * HDBSCAN minimum cluster size (minimum photos to form a Person)
     */
    public int $hdbscanMinCluster = 2;

    /**
     * HDBSCAN minimum samples (density threshold; 2 prevents false merges)
     */
    public int $hdbscanMinSamples = 2;

    /**
     * Default 512-dimensional CLIP model for semantic natural language search
     */
    public string $clipModelName = 'openai/clip-vit-base-patch32';

    /**
     * YOLOv8 object tagging threshold (0.1 - 1.0)
     */
    public float $objectDetThresh = 0.5;
}
