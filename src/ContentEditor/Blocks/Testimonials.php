<?php

namespace Skeletor\ContentEditor\Blocks;

use Skeletor\ContentEditor\Contracts\BlockParserInterface;
use Skeletor\Core\Service\Contracts\CrudServiceInterface;

class Testimonials implements BlockParserInterface
{
    const NAME = 'testimonials';

    public function __construct(protected CrudServiceInterface $imageService)
    {

    }

    public function parse(array $data, array $customDataKeys = []): array
    {
        $customDataKeys = array_merge($customDataKeys, $this->getDefaultDataKeys());
        $blockData = $data[static::NAME] ?? [];

        $parsedData =  [
            'type' => static::NAME,
            'title' => $blockData['title'] ?? '',
            'testimonials' => $this->parseTestimonials($blockData['testimonials'] ?? []),
        ];

        foreach($customDataKeys as $key) {
            if(isset($data[$key])) {
                $parsedData[$key] = $data[$key];
            }
        }

        return $parsedData;
    }

    protected function parseTestimonials(array $testimonials): array
    {
        $testimonialsData = [];
        foreach($testimonials as $testimonial) {
            $testimonialsData[] = $this->parseTestimonial($testimonial);
        }
        return $testimonialsData;
    }

    protected function parseTestimonial(array $testimonial): array
    {
        $imageData = $this->getImageDataForTestimonial($testimonial);
        return [
            'review' => $testimonial['review'] ?? '',
            'name' => $testimonial['name'] ?? '',
            'rating' => $testimonial['rating'] ?? '',
            'imageId' => $imageData['imageId'] ?? '',
            'filename' => $imageData['filename'] ?? '',
        ];
    }


    protected function getImageDataForTestimonial(array $testimonial): array
    {
        if(!$testimonial['imageId']) {
            $image = null;
        } else {
            $image = $this->imageService->getById($testimonial['imageId']);
        }
        return [
            'imageId' => $image?->id,
            'filename' => $image?->filename,
        ];
    }

    protected function getDefaultDataKeys(): array
    {
        return [
            'blockHTMLId',
            'blockHTMLClassName',
            'blockViewMode',
            'containerMarginTop',
            'containerMarginBottom',
            'containerMarginLeft',
            'containerMarginRight',
            'containerPaddingTop',
            'containerPaddingBottom',
            'containerPaddingLeft',
            'containerPaddingRight'
        ];
    }
}