<?php


namespace CsrDelft\view\bbcode\prosemirror;


use CsrDelft\bb\tag\BbNode;
use CsrDelft\view\bbcode\tag\embed\BbYoutube;

class NodeYoutube implements Node
{
	public static function getBbTagType()
	{
		return BbYoutube::class;
	}

	public static function getNodeType()
	{
		return 'youtube';
	}

	public function getData(BbNode $node)
	{
		if (!$node instanceof BbYoutube) {
			throw new \Exception();
		}
		return [
			'attrs' => [
				'url' => $node->id
			]
		];
	}

	public function getTagAttributes($node)
	{
		return [
			'youtube' => $node->attrs->url,
		];
	}

	public function selfClosing()
	{
		return true;
	}
}