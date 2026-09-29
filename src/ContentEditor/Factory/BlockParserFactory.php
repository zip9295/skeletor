<?php

namespace Skeletor\ContentEditor\Factory;

use Skeletor\ContentEditor\Blocks\Accordion;
use Skeletor\ContentEditor\Blocks\Banner;
use Skeletor\ContentEditor\Blocks\Cards;
use Skeletor\ContentEditor\Blocks\Divider;
use Skeletor\ContentEditor\Blocks\EmailCollector;
use Skeletor\ContentEditor\Blocks\Embed;
use Skeletor\ContentEditor\Blocks\Gallery;
use Skeletor\ContentEditor\Blocks\Heading;
use Skeletor\ContentEditor\Blocks\Hero;
use Skeletor\ContentEditor\Blocks\Html;
use Skeletor\ContentEditor\Blocks\Image;
use Skeletor\ContentEditor\Blocks\ListPoints;
use Skeletor\ContentEditor\Blocks\NavigationTabs;
use Skeletor\ContentEditor\Blocks\Pricing;
use Skeletor\ContentEditor\Blocks\Quote;
use Skeletor\ContentEditor\Blocks\Slider;
use Skeletor\ContentEditor\Blocks\Table;
use Skeletor\ContentEditor\Blocks\Tabs;
use Skeletor\ContentEditor\Blocks\Testimonials;
use Skeletor\ContentEditor\Blocks\TextEditor;
use Skeletor\ContentEditor\Contracts\BlockParserFactoryInterface;
use Skeletor\ContentEditor\Contracts\BlockParserInterface;
use Skeletor\Core\Service\Contracts\CrudServiceInterface;

class BlockParserFactory implements BlockParserFactoryInterface
{

    protected array $blockParsers = [];
    public function __construct(protected CrudServiceInterface $imageService)
    {
        $this->registerBaseBlockParsers();
    }

    protected function registerBaseBlockParsers(): void
    {
        $this->registerBlockParser(TextEditor::NAME, new TextEditor());
        $this->registerBlockParser(Image::NAME, new Image($this->imageService));
        $this->registerBlockParser(Gallery::NAME, new Gallery($this->imageService));
        $this->registerBlockParser(Embed::NAME, new Embed());
        $this->registerBlockParser(Quote::NAME, new Quote());
        $this->registerBlockParser(Slider::NAME, new Slider($this->imageService));
        $this->registerBlockParser(Heading::NAME, new Heading());
        $this->registerBlockParser(Hero::NAME, new Hero($this->imageService));
        $this->registerBlockParser(Accordion::NAME, new Accordion());
        $this->registerBlockParser(Table::NAME, new Table());
        $this->registerBlockParser(Banner::NAME, new Banner($this->imageService));
        $this->registerBlockParser(Pricing::NAME, new Pricing());
        $this->registerBlockParser(ListPoints::NAME, new ListPoints());
        $this->registerBlockParser(Divider::NAME, new Divider());
        $this->registerBlockParser(Html::NAME, new Html());
        $this->registerBlockParser(Testimonials::NAME, new Testimonials($this->imageService));
        $this->registerBlockParser(NavigationTabs::NAME, new NavigationTabs());
        $this->registerBlockParser(Tabs::NAME, new Tabs($this->imageService));
        $this->registerBlockParser(Cards::NAME, new Cards($this->imageService));
        $this->registerBlockParser(EmailCollector::NAME, new EmailCollector());
    }

    public function createParser(string $blockName): BlockParserInterface
    {
        if(!isset($this->blockParsers[$blockName])) {
            throw new \Exception('Block parser not found');
        }
        return $this->blockParsers[$blockName];
    }

    public function registerBlockParser(string $blockName, BlockParserInterface $blockParser): void
    {
        $this->blockParsers[$blockName] = $blockParser;
    }
}