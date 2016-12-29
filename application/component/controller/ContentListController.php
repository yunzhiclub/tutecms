<?php
namespace app\component\controller;

use think\Request;

use app\model\FieldDataBodyModel;
use app\model\UserModel;  
use app\model\ContentFrontpageModel;
use app\model\ContentModel;                 // 文章
use app\model\FieldModel;                   // 扩展字段

/**
 * todo:权限判断。即当前新闻，是否属于当前这个菜单对应的那个 新闻类别
 */
class ContentListController extends ComponentController
{
    public function indexAction()
    {
        $ContentModel = new ContentModel();
        $map = [];
        $map['content_type_name'] = $this->config['contentTypeName']['value'];
        $map['is_freezed'] = '0';
        $map['is_delete'] = '0';
        $count = $this->getSampleConfig()['count'];
        $ContentModels = $ContentModel->where($map)->order('id desc')->paginate($count);
        $this->assign('ContentModels', $ContentModels);
        
        //获取当前用户信息
        $User = UserModel::getCurrentFrontUserModel();
        $this->assign('User', $User);
        $this->assign('Url', $this->config['contentTypeName']['value']);
        return $this->fetch();
    }


    public function readAction($id)
    {
        $map = ['id' => $id];
        $ContentModel = ContentModel::get($map);
        if ('' === $ContentModel->getData('id')) {
            return $this->error('数据不存在或已删除');
        }
        $ContentModel->FieldXXXXModels();
        $this->assign('ContentModel', $ContentModel);
        //获取操作说明的列表
        $ContentModel = new ContentModel();
        $map = [];
        $map['content_type_name'] = $this->config['contentTypeName']['value'];
        $map['is_freezed'] = '0';
        $map['is_delete'] = '0';
        $ContentModels = $ContentModel->where($map)->paginate(10);
        $this->assign('ContentModels', $ContentModels);

        // 增加一个点击量
        $ContentModel = ContentModel::get($id);
        $ContentModel->hit = $ContentModel->hit + 1;
        $ContentModel->save();
        //获取当前用户信息
        $User = UserModel::getCurrentFrontUserModel();
        $this->assign('User', $User);
        $this->assign('Url', $this->config['contentTypeName']['value']);

        return $this->fetch();
    }

    public function editAction($id)
    {
        // 更新当前新闻信息
        $ContentModel = ContentModel::get(['id' => $id]);

        $this->assign('ContentModel', $ContentModel);

        return $this->fetch();
    }


    public function updateAction($id)
    {
        
        // 更新当前新闻信息
        $ContentModel = ContentModel::get(['id' => $id]);

        // 获取数据
        $data = Request::instance()->param();

        // 更新当前新闻信息
        $ContentModel->setData('title', $data['title']);
        $ContentModel->save();
        // 更新扩展数据字段
        if (isset($data['field_'])) {
            FieldModel::updateLists($data['field_'], $ContentModel->getData('id'));
        }
        // 成功返回
        return $this->success('操作成功', url('@' . $this->currentMenuModel->getData('url')));
    }

    //删除数据
    public function deleteAction($id) {
        //删除content表中对应的内容
        $ContentModel = ContentModel::get($id);
        $ContentModel->setData('is_delete', 1);
        $ContentModel->save();
        //删除fieldDataBody表中对应的内容
        $FieldDataBodyModel = FieldDataBodyModel::get($id);
        $FieldDataBodyModel->setData('is_delete', 1);
        $FieldDataBodyModel->save();
        // 删除contentFrontpage表中对应的内容
        $map['content_id'] = $id;
        $ContentFrontpageModel = ContentFrontpageModel::get($map)->getData();
        if (false === empty($ContentFrontpageModel)) {
            $ContentFrontpageModel = ContentFrontpageModel::get($map);
            $ContentFrontpageModel->delete();
        }
       
        return $this->success('删除成功');
    }

    public function addAction() {
        return $this->fetch();
    }

    //保存信息
    public function saveAction() {
        //将标题信息保存到content表中
        $data = Request::instance()->param();
        $ContentModel = new ContentModel();
        $ContentModel->setData('title', $data['title']);
        $ContentModel->setData('content_type_name', $this->config['contentTypeName']['value']);
        if (false === $ContentModel->save()) {
            return $this->error($ContentModel->getError());
        }
        $keyId = $ContentModel->getData('id');
        //将文章信息保存到ContentFrontpageModel中
        $ContentFrontpageModel = new ContentFrontpageModel;
        $ContentFrontpageModel->setData('content_id', $keyId);
        if (false === $ContentFrontpageModel->save()) {
            return $this->error($ContentModel->getError());
        }

        //将内容信息保存到fieldDataBody表中
        $FieldModel = new FieldModel();
        $map['relate_value'] = $this->config['contentTypeName']['value'];
        $fieldId = FieldModel::get($map)->getData('id');

        //将文章信息存放在fieldDataBody中
        $FieldDataBodyModel = new FieldDataBodyModel;
        $FieldDataBodyModel->setData('field_id', $fieldId);
        $FieldDataBodyModel->setData('key_id', $keyId);
        $FieldDataBodyModel->setData('value', $data['content']);
        if (false === $FieldDataBodyModel->save()) {
            return $this->error($FieldDataBodyModel->getError());
        }
        // 更新扩展数据字段
        if (isset($data['field_'])) {
            FieldModel::updateLists($data['field_'], $this->ContentModel->getData('id'));
        }
        // 成功返回
        return $this->success('操作成功', url('@' . $this->currentMenuModel->getData('url')));
    }
}
