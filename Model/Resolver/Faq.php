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

use MageINIC\Faq\Api\Data\FaqSearchResultsInterfaceFactory;
use MageINIC\Faq\Api\FaqRepositoryInterface;
use MageINIC\Faq\Model\ResourceModel\Faq\CollectionFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder as SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Model\Template\FilterEmulate;

/**
 * Resolve Faq.
 */
class Faq implements ResolverInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var ServiceOutputProcessor
     */
    private ServiceOutputProcessor $serviceOutputProcessor;

    /**
     * @var FilterBuilder
     */
    private FilterBuilder $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private FilterGroupBuilder $filterGroupBuilder;

    /**
     * @var FaqRepositoryInterface
     */
    private FaqRepositoryInterface $faqRepository;

    /**
     * Construct Method
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param FaqRepositoryInterface $faqRepository
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     */
    public function __construct(
        SearchCriteriaBuilder            $searchCriteriaBuilder,
        SortOrderBuilder                 $sortOrderBuilder,
        ServiceOutputProcessor           $serviceOutputProcessor,
        StoreManagerInterface            $storeManager,
        CollectionFactory                $collectionFactory,
        FaqRepositoryInterface           $faqRepository,
        FilterBuilder                    $filterBuilder,
        FilterGroupBuilder               $filterGroupBuilder
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder      = $sortOrderBuilder;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->storeManager = $storeManager;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->faqRepository = $faqRepository;
    }

    /**
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|Value|mixed
     * @throws GraphQlInputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $this->validateArgs($args);
        try {
            $searchCriteria = $this->searchCriteriaBuilder->build('faq', $args);
            $searchCriteria->setCurrentPage($args['currentPage']);
            $searchCriteria->setPageSize($args['pageSize']);
            $sliderId = isset($value['category_id']) ? $value['category_id'] : $args['filter']['category_id']['eq'];
            $categoryFilter = $this->filterBuilder
                ->setField('category_id')
                ->setConditionType('eq')
                ->setValue($sliderId)
                ->create();
            $filterGroup = $this->filterGroupBuilder->addFilter($categoryFilter)->create();
            $searchCriteria->setFilterGroups([$filterGroup]);
            $searchResult = $this->faqRepository->getList($searchCriteria);
            $totalCount = $searchResult->getTotalCount();
            $faqDataList = [];
            foreach ($searchResult->getItems() as $post) {
                $faqData = $this->serviceOutputProcessor->process(
                    $post,
                    FaqRepositoryInterface::class,
                    'getById'
                );
                $faqData['faq_id'] = $post->getId();
                $faqData['status'] = $post->getStatus();
                $faqData['title'] = $post->getTitle();
                $faqData['answer'] = $post->getAnswer();
                $faqData['sender_name'] = $post->getSenderName();
                $faqData['sender_email'] = $post->getSenderEmail();
                $faqData['visibility'] = $post->getVisibility();
                $faqData['most_frequently'] = $post->getMostFrequently();
                $faqDataList[] = $faqData;
            }
            return ['items' => $faqDataList, 'total_count' => $totalCount];
        } catch (\Exception $e) {

            return ['error' => $e->getMessage()];
        }
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
}
