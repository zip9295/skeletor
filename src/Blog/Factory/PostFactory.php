<?php

namespace Skeletor\Blog\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Skeletor\Author\Entity\Author;
use Skeletor\Blog\Entity\Category;
use Skeletor\Blog\Entity\Post;
use Skeletor\Blog\Entity\Tag;
use Skeletor\Core\Factory\AbstractFactory;
use Skeletor\Image\Entity\Image;

class PostFactory extends AbstractFactory
{
    public static function compileEntityForCreate($data, $em): ?int
    {
        $post = new Post();
        $post->title = $data['title'];
        $post->slug = $data['slug'];
        $post->shortDescription = $data['shortDescription'];
        $post->blockData = $data['blockData'];
        $post->status = $data['status'];
        $post->isLiveBlogPost = $data['isLiveBlogPost'];
        $post->publishAt = $data['publishAt'];
        if($data['author']) {
            $author = $em->getRepository(Author::class)->find((int)$data['author']);
            if($author) {
                $post->author = $author;
            }
        }
        if(isset($data['featuredImageId'])) {
            $image = $em->getRepository(Image::class)->find($data['featuredImageId']);
            $post->featuredImage = $image;
        }
        // find() returns null for an id that no longer exists -- a tag deleted while the form
        // was open, a stale id in a resubmitted request. Adding that null to the collection
        // makes Doctrine fail on flush with an error that names neither the tag nor the post,
        // so a vanished tag is skipped instead.
        $tags = new ArrayCollection();
        foreach($data['tags'] ?? [] as $tagId) {
            $tag = $em->getRepository(Tag::class)->find((int)$tagId);
            if($tag) {
                $tags->add($tag);
            }
        }
        $categories = new ArrayCollection();
        foreach($data['categories'] ?? [] as $catId) {
            $category = $em->getRepository(Category::class)->find((int)$catId);
            if($category) {
                $categories->add($category);
            }
        }
        if(isset($data['mainCategory'])) {
            $category = $em->getRepository(Category::class)->find($data['mainCategory']);
            $post->mainCategory = $category;
        }
        $post->categories = $categories;
        $post->tags = $tags;
        $post->seoTitle = $data['seoTitle'] ?? $post->title;
        $post->seoDescription = $data['seoDescription'] ?? $post->shortDescription;
        if(isset($data['seoImageId'])) {
            $image = $em->getRepository(Image::class)->find($data['seoImageId']);
            $post->seoImage = $image;
        }
        $em->persist($post);
        $em->flush();

        return $post->id;
    }

    public static function compileEntityForUpdate($data, $em)
    {
        $post = $em->getRepository(Post::class)->find($data['id']);
        $post->title = $data['title'];
        $post->slug = $data['slug'];
        $post->shortDescription = $data['shortDescription'];
        $post->blockData = $data['blockData'];
        $post->status = $data['status'];
        $post->publishAt = $data['publishAt'];
        if($data['author']) {
            $author = $em->getRepository(Author::class)->find((int)$data['author']);
            if($author) {
                $post->author = $author;
            }
        }
        $post->isLiveBlogPost = $data['isLiveBlogPost'];
        if(isset($data['featuredImageId'])) {
            $image = $em->getRepository(Image::class)->find($data['featuredImageId']);
            $post->featuredImage = $image;
        } else {
            $post->featuredImage = null;
        }
        // find() returns null for an id that no longer exists -- a tag deleted while the form
        // was open, a stale id in a resubmitted request. Adding that null to the collection
        // makes Doctrine fail on flush with an error that names neither the tag nor the post,
        // so a vanished tag is skipped instead.
        $tags = new ArrayCollection();
        foreach($data['tags'] ?? [] as $tagId) {
            $tag = $em->getRepository(Tag::class)->find((int)$tagId);
            if($tag) {
                $tags->add($tag);
            }
        }
        $categories = new ArrayCollection();
        foreach($data['categories'] ?? [] as $catId) {
            $category = $em->getRepository(Category::class)->find((int)$catId);
            if($category) {
                $categories->add($category);
            }
        }
        if(isset($data['mainCategory'])) {
            $category = $em->getRepository(Category::class)->find($data['mainCategory']);
            $post->mainCategory = $category;
        }
        $post->categories = $categories;
        $post->tags = $tags;
        $post->seoTitle = $data['seoTitle'] ?? $post->title;
        $post->seoDescription = $data['seoDescription'] ?? $post->shortDescription;
        if(isset($data['seoImageId'])) {
            $image = $em->getRepository(Image::class)->find($data['seoImageId']);
            $post->seoImage = $image;
        } else {
            $post->seoImage = null;
        }

        return $post->id;
    }
}