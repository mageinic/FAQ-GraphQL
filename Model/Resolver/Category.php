<?php
/**
 * MageINIC
 * Copyright (C) 2023 MageINIC <support@mageinic.com>
 *
 * NOTICE OF LICENSE
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://opensource.org/licenses/gpl-3.0.html.
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category MageINIC
 * @package MageINIC_FaqGraphql
 * @copyright Copyright (c) 2023 MageINIC (https://www.mageinic.com/)
 * @license https://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author MageINIC <support@mageinic.com>
 */

namespace MageINIC\FaqGraphql\Model\Resolver;

use MageINIC\Faq\Api\Data\CategorySearchResultsInterfaceFactory;
use MageINIC\Faq\Api\CategoryRepositoryInterface;
use MageINIC\Faq\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder as SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolve Category.
 */
class Category implements ResolverInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var ServiceOutputProcessor
     */
    private ServiceOutputProcessor $serviceOutputProcessor;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private CollectionProcessorInterface $collectionProcessor;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;


    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param CategorySearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ServiceOutputProcessor $serviceOutputProcessor,
        CategorySearchResultsInterfaceFactory $searchResultsFactory,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlInputException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $this->validateArgs($args);
        $searchCriteria = $this->searchCriteriaBuilder->build('category', $args);
        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);

        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $totalCount = $collection->getSize();
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());

        $postData = [
            "items" => [],
            "total_count" => $totalCount
        ];
        foreach ($searchResults->getItems() as $post) {
            $categoryData = $this->serviceOutputProcessor->process(
                $post,
                CategoryRepositoryInterface::class,
                'getById'
            );
            $categoryData['category_id'] = $post->getId();
            $categoryData['image'] = $this->getCategoryImage($post->getImage());
            $categoryData['status'] = $post->getStatus();
            $categoryData['category_name'] = $post->getCategoryName();
            $postData["items"][] = $categoryData;
        }

        return $postData;
    }

    /**
     * Validate Args
     *
     * @param array $args
     * @throws GraphQlInputException
     */
    private function validateArgs(array $args): void
    {
        if (isset($args['currentPage']) && $args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }

        if (isset($args['pageSize']) && $args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
    }

    /**
     * Get Category Image
     *
     * @param $image
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCategoryImage($image): string
    {
        $url =$this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return $url . 'MageINIC/faq/image/' . $image;
    }
}
