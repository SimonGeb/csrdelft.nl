<?php

namespace CsrDelft\view\bbcode\prosemirror;

use CsrDelft\bb\tag\BbCode;
use CsrDelft\bb\tag\BbNode;

class NodeCodeBlock implements Node
{
	public function getBbTagType()
	{
		return BbCode::class;
	}

	public function getNodeType()
	{
		return 'code_block';
	}

	public function getData(BbNode $node)
	{
		return [
			'type' => 'code_block',
		];
	}

	public function getTagAttributes($node)
	{
		return [];
	}

	public function selfClosing()
	{
		return false;
	}
}
