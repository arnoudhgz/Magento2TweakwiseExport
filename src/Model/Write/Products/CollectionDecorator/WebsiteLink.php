<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\DbResourceHelper;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db_Statement_Exception;

class WebsiteLink implements DecoratorInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DbResourceHelper
     */
    private $dbResource;

    /**
     * WebsiteLink constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param DbResourceHelper $dbResource
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DbResourceHelper $dbResource
    ) {
        $this->dbResource = $dbResource;
        $this->storeManager = $storeManager;
    }

    /**
     * Decorate items with extra data or remove items completely
     *
     * @param Collection $collection
     * @throws Zend_Db_Statement_Exception
     */
    public function decorate(Collection $collection)
    {
        if ($this->storeManager->isSingleStoreMode()) {
            return;
        }

        $this->addLinkedWebsiteIds($collection);
        $this->ensureWebsiteLinkedSet($collection);
    }

    /**
     * @return string
     */
    private function getProductWebsiteTable(): string
    {
        return $this->dbResource->getTableName('catalog_product_website');
    }

    /**
     * @param Collection $collection
     * @throws Zend_Db_Statement_Exception
     */
    private function addLinkedWebsiteIds(Collection $collection)
    {
        $select = $this->dbResource->getConnection()->select()
            ->from($this->getProductWebsiteTable(), ['product_id', 'website_id'])
            ->where('product_id in(' . implode(',', $collection->getIds()) . ')');
        $query = $select->query();

        while ($row = $query->fetch()) {
            $productId = (int)$row['product_id'];
            $collection->get($productId)->addLinkedWebsiteId((int)$row['website_id']);
        }
    }

    /**
     * @param Collection $collection
     */
    private function ensureWebsiteLinkedSet(Collection $collection)
    {
        /** @var ExportEntity $entity */
        foreach ($collection as $entity) {
            $entity->ensureWebsiteLinkedIdsSet();
        }
    }
}