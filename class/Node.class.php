<?php
class Node {
    protected $conf = [
        'id' => 'id',                // 唯一标识
        'level' => 'level',          // 父节点
        'attr' => 'attr',            // 属性
        'children' => 'children'     // tree 子节点
    ];
    
    protected $data = [];           // 要操作的原始数据
    protected $datas = [];          // 已处理完成的数据
    
    public function __construct($conf = []) {
        $this->conf = array_merge($this->conf , $conf);
    }

    public function __get($name = ''){
        if(isset($this->conf[$name])){
            return $this->conf[$name];
        }else{
            return null;
        }
    }
    
    public function __set($name = '' , $value = ''){
        if(isset($this->conf[$name])){
            $this->conf[$name] = $value;
        }
    }
    
    /* 设置配置 */
    public function conf($name = '' , $value = ''){
        if(is_array($name)){
            $this->conf = array_merge($this->conf , $name);
        }else if(isset($this->conf[$name])){
            $this->conf[$name] = $value;
        }
        return $this;
    }
    
    /* 设置数据 */
    public function data($data = [] , $replace = true){
        if(true === $replace){
            $this->data = $data;
        }else{
            array_push($this->data , $data);
        }
        return $this;
    }
    
    /* 格式化节点数据 */
    protected function formatData(){
        $data = $this->data;
        foreach($data as &$v){
            $v[$this->id] = (string)$v[$this->id];
            $v[$this->level] = (string)$v[$this->level];
            $v[$this->attr] = [
                'parents' => [],
                'children' => [],
                'childrens' => [],
                'deepth' => 0
            ];
        }
        if( function_exists('array_column') ){
            $this->datas = array_column($data , null , $this->id);
        }else{
            $datas = [];
            foreach($data as $v){
                $datas[$v[$this->id]] = $v;
            }
            $this->datas = $datas;
        }
    }
    
    /* 获取所有父节点 */
    protected function getParents($datas , $id , $parents = []){
        if( !empty($datas) && isset($datas[$id]) ){
            $level = $datas[$id][$this->level];
            unset($datas[$id]);
            if( isset($datas[$level]) ){
                array_unshift($parents , $level);
                $parents = $this->getParents($datas , $level , $parents);    
            }
        }
        return $parents;
    }
    
    /* 获取子节点(仅一级) */
    protected function getChildren($datas , $id){
        $children = [];
        foreach($datas as $v){
            if($v[$this->level] === $id){
                $children[] = $v[$this->id];
            }
        }
        return $children;
    }
    
    /* 获取所有子节点 */
    protected function getChildrens($datas , $id){
        $children = $datas[$id][$this->attr]['children'];
        $childrens = [];
        if(!empty($children)){
            foreach($children as $v){
                $childrens[] = $v;
                $childrens = array_merge($childrens , $this->getChildrens($datas , $v));
            }
        }
        return $childrens;
    }
    
    /* 生成tree */
    protected function createTree($datas , $level){
        $tree = [];
        $level = (string)$level;
        if( !empty($datas) ){
            foreach($datas as $id => $v){
                if($level === $v[$this->level]){
                    unset($datas[$id]);
                    $v[$this->children] = $this->createTree($datas , $id);
                    $tree[$id] = $v;
                }
            }    
        }
        return $tree;
    }
    
    /* 返回节点数据 */
    public function nodes(){
        return $this->datas;
    }
    
    /* 返回指定level的tree */
    public function tree($level = 0 , $datas = null){
        $datas = is_null($datas) ? $this->datas : $datas;
        $tree = $this->createTree($datas , $level);
        return $tree;
    }
    
    /* 初始化 */
    public function init($data = null , $replace = true){
        if(!is_null($data)){
            $this->data($data , $replace);
        }
        $this->formatData();
        
        $datas = $this->datas;
        foreach($this->datas as $id => $v){
            $this->datas[$id][$this->attr]['parents'] = $this->getParents($datas , $v[$this->id]);
            $this->datas[$id][$this->attr]['deepth'] = count($this->datas[$id][$this->attr]['parents']);
            $this->datas[$id][$this->attr]['children'] = $this->getChildren($datas , $v[$this->id]);
        }
        
        $datas = $this->datas;
        foreach($this->datas as $id => $v){
            $this->datas[$id][$this->attr]['childrens'] = $this->getChildrens($datas , $v[$this->id]);
        }
        
        return $this;
    }
}
