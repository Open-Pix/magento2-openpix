<?php

namespace OpenPix\Pix\Helper;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\Framework\Exception\InputException;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SalesRule\Api\Data\RuleInterfaceFactory;

class Coupon
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CouponRepositoryInterface
     */
    protected $couponRepository;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var Rule
     */
    protected $rule;

    /**
     * @var CouponInterface
     */
    protected $coupon;

    public function __construct(
        CouponRepositoryInterface $couponRepository,
        RuleRepositoryInterface $ruleRepository,
        RuleInterfaceFactory $rule,
        CouponInterface $coupon,
        LoggerInterface $logger
    ) {
        $this->couponRepository = $couponRepository;
        $this->ruleRepository = $ruleRepository;
        $this->rule = $rule;
        $this->coupon = $coupon;
        $this->logger = $logger;
    }

    /**
     * Create Rule
     *
     * @return void
     */
    public function createRule(int $giftbackAppliedValue, $orderId)
    {
        $newRule = $this->rule->create();
        $newRule
            ->setName('GIFTBACK')
            ->setDescription('')
            ->setIsAdvanced(true)
            ->setStopRulesProcessing(false)
            ->setDiscountQty(1)
            ->setCustomerGroupIds([0, 1, 2])
            ->setWebsiteIds([1])
            ->setIsRss(1)
            ->setUsesPerCoupon(1)
            ->setDiscountStep(0)
            ->setCouponType(RuleInterface::COUPON_TYPE_SPECIFIC_COUPON)
            ->setSimpleAction(
                RuleInterface::DISCOUNT_ACTION_FIXED_AMOUNT_FOR_CART
            )
            ->setDiscountAmount($giftbackAppliedValue / 100)
            ->setIsActive(true);

        try {
            $ruleCreate = $this->ruleRepository->save($newRule);
            //If rule generated, Create new Coupon by rule id
            if ($ruleCreate->getRuleId()) {
                return $this->createCoupon($ruleCreate->getRuleId(), $orderId);
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * Create Coupon by Rule id.
     *
     * @param int $ruleId
     *
     * @return int|null
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createCoupon(int $ruleId, int $orderId)
    {
        /** @var CouponInterface $coupon */
        $coupon = $this->coupon;
        $couponCode = "giftback-$orderId";
        $coupon
            ->setCode($couponCode)
            ->setIsPrimary(1)
            ->setRuleId($ruleId)
            ->setUsageLimit(1);

        /** @var CouponRepositoryInterface $couponRepository */
        $coupon = $this->couponRepository->save($coupon);
        return $coupon->getCode();
    }
}
