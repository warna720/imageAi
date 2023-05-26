<?php

namespace Awssat\ImageAi;

use Intervention\Image\Image;

class PythonScript
{
    public $percentage = 70;
    public $customObjects =  [];
    public $currentDirPath;
    public $imagePath;
    public $imageDetectedPath;
    public $modelType = 'RetinaNet';
    public $modelPath;
    public $speed;

    /**
     * PythonScript constructor.
     * @param Image $image
     */
    public function __construct(Image $image)
    {
        $this->currentDirPath = dirname(__FILE__);

        $this->imagePath = $this->currentDirPath . "/" .
            ($image->filename ?? 'random') . '.' . ($image->extension ?? 'png');

        $this->imageDetectedPath = $this->currentDirPath . "/" .
            $image->filename . 'Detected.' . $image->extension;

        $this->modelPath =  $this->currentDirPath . "/" . "resnet50_coco_best_v2.0.1.h5";
    }

    protected function objects()
    {
        if(!empty($this->customObjects)) {
            $objects = implode('=True,', $this->customObjects) . '=True';
            return <<<EOF
custom_objects = detector.CustomObjects($objects)
detections = detector.detectCustomObjectsFromImage(custom_objects=custom_objects,
EOF;
        }

        return "detections = detector.detectObjectsFromImage(";
    }

    protected function speed()
    {
        if(!$this->speed) {
            return '';
        }

        return "detection_speed=\"{$this->speed}\"";
    }

    public function generate()
    {
        return <<<EOF
from imageai.Detection import ObjectDetection
import os

detector = ObjectDetection()
detector.setModelTypeAs$this->modelType()
detector.setModelPath("$this->modelPath")
detector.loadModel({$this->speed()})
{$this->objects()}input_image="$this->imagePath", minimum_percentage_probability=$this->percentage, extract_detected_objects=False)

objects = []
for eachObject in detections:
    objects.append({"name": eachObject["name"], "percentage": eachObject["percentage_probability"], "box_points": eachObject["box_points"]})

print(objects)
EOF;
    }
}
