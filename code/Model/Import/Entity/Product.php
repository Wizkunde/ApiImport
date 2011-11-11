<?php
/*
 * Copyright 2011 Daniel Sloof
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
*/

class Danslo_ApiImport_Model_Import_Entity_Product extends Mage_ImportExport_Model_Import_Entity_Product {
    
    public function __construct() {
        parent::__construct();
        $this->_dataSourceModel = Danslo_ApiImport_Model_Import::getDataSourceModel();
    }
    
    protected function _indexStock(&$event) {
        return Mage::getResourceSingleton('cataloginventory/indexer_stock')->catalogProductMassAction($event);
    }
    
    protected function _indexPrice(&$event) {
        return Mage::getResourceSingleton('catalog/product_indexer_price')->catalogProductMassAction($event);
    }
    
    protected function _indexCategoryRelation(&$event) {
        return Mage::getResourceSingleton('catalog/category_indexer_product')->catalogProductMassAction($event);
    }
    
    protected function _indexEav(&$event) {
        return Mage::getResourceSingleton('catalog/product_indexer_eav')->catalogProductMassAction($event);
    }
    
    protected function _indexSearch(&$productIds) {
        return Mage::getResourceSingleton('catalogsearch/fulltext')->rebuildIndex(null, $productIds);
    }
    
    protected function _indexEntities() {
        /*
         * Run some of the indexers for newly imported entities.
         */
        $entities = array();
        foreach($this->_newSku as $sku) {
            $entities[] = $sku['entity_id'];
        }
        
        /*
         * Set up event object for transporting our product ids.
         */
        $event = Mage::getModel('index/event');
        $event->setNewData(array(
            'product_ids'               => &$entities, // for category_indexer_product
            'reindex_price_product_ids' => &$entities, // for product_indexer_price
            'reindex_stock_product_ids' => &$entities, // for indexer_stock
            'reindex_eav_product_ids'   => &$entities  // for product_indexer_eav
        ));

        /*
         * Rebuild indexes that are essential to basic functionality.
         */
        try {
            $this->_indexStock($event);
            $this->_indexPrice($event);
            $this->_indexCategoryRelation($event);
            $this->_indexEav($event);
            $this->_indexSearch($entities);
        } 
        catch(Exception $e) {
            return false;
        }

        return true;
    }
    
    public function _importData() {
        $result = parent::_importData();
        if($result) {
            $result = $this->_indexEntities();
        }
        return $result;
    }
    
}
