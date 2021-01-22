<?php


namespace CsrDelft\view\bbcode\prosemirror;


use CsrDelft\bb\tag\BbNode;
use CsrDelft\bb\tag\BbUnderline;

class MarkUnderline implements Mark
{
	public function getBbTagType()
	{
		return BbUnderline::class;
	}

	public function getData(BbNode $node)
	{
		return [
			'type' => 'underline',
		];
	}

	public function getMarkType()
	{
		return 'underline';
	}

	public function getTagName()
	{
		return 'u';
	}

	public function getTagAttributes($mark)
	{
		return [];
	}
}
