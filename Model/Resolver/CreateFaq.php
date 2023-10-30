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

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use MageINIC\Faq\Model\FaqRepository;
use MageINIC\Faq\Model\FaqFactory;

/**
 * Faq Create Faq
 */
class CreateFaq implements ResolverInterface
{
    /**
     * @var FaqRepository
     */
    private FaqRepository $faqRepository;

    /**
     * @var FaqFactory
     */
    private FaqFactory $faqFactory;

    /**
     * @param FaqRepository $faqRepository
     * @param FaqFactory $faqFactory
     */
    public function __construct(
        FaqRepository $faqRepository,
        FaqFactory $faqFactory
    ) {
        $this->faqRepository = $faqRepository;
        $this->faqFactory = $faqFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        try {
            $faqInput = $args['input'];
            $faq = $this->faqFactory->create();
            $faq->setData($faqInput);
            $this->faqRepository->save($faq);
            return $faq->getData();
        } catch (\Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
    }
}
