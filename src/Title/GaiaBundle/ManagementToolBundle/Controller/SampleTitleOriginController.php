<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;


use Gaia\Bundle\ManagementToolBundle\Controller\Abstracts\WithSideAndTabMenuController;

// TODO ManagementTool 基底クラスを拡張しコントローラを実装してください

class SampleTitleOriginController extends WithSideAndTabMenuController
{
    public function sample1Action()
    {
        $data = ['data' => 'タイトル独自実装画面 １'];
        return $this->render('TitleManagementToolBundle:sample:sample1.html.twig', $data);
    }

    public function sample2Action()
    {
        $data = ['data' => 'タイトル独自実装画面 2'];
        return $this->render('TitleManagementToolBundle:sample:sample2.html.twig', $data);
    }
}