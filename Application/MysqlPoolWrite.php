<?php
declare(strict_types=1);
namespace App;

use Swoole;
use Co;
use Chan;
use Swoole\Coroutine;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Runtime;

class MysqlPoolWrite
{
    const N = 1;
    protected $pool;
    function __construct()
    {
        $this->initPool();
        $this->run();
    }

    function initPool(){
        if(!$this->pool){
            $pool = new PDOPool((new PDOConfig)
                ->withHost('192.168.111.1')
                ->withPort(3306)
                // ->withUnixSocket('/tmp/mysql.sock')
                ->withDbName('css_ii_admin')
                ->withCharset('utf8mb4')
                ->withUsername('magento2')
                ->withPassword('')
            );
            $this->pool= $pool;
        }
    }

    function run()
    {
        Runtime::enableCoroutine();
        $s = microtime(true);
        Co\run(function () {
            for ($n = self::N; $n--;) {
                go(function () use ($n) {
                    $pdo = $this->pool->get();
                    foreach(range(200001 ,300000) as $id){

                     $sql = "INSERT INTO `css_ii_order`.`orders`(`id`, `order_sn`, `pay_real_sn`, `status`, `oms_status`, `applied_rule_ids`, `total_qty_ordered`, `shipping_amount`, `wechat_id`, `customer_id`, `customer_name`, `customer_gender`, `customer_salute`, `customer_level`, `customer_note_notify`, `service_type`, `store_id`, `store_name`, `store_address`, `store_open_hour`, `store_mobile`, `shipping_id`, `shipping_description`, `ship_method`, `shipping_time`, `consignee`, `province`, `city`, `district`, `address`, `phone`, `phone_code`, `postal_code`, `pay_method`, `pay_code`, `transaction_id`, `pay_time`, `refund_time`, `refund_times`, `refund_amount`, `invc_url`, `policyUrl`, `invoice_title`, `invoice_code`, `invoice_type`, `total_amount`, `pdttotal`, `promotionDiscount`, `promotionSale`, `coupon_id`, `coupon_discount`, `code_id`, `code_discount`, `discount`, `give_point`, `point_id`, `point`, `point_discount`, `member_discount`, `sale_pdt`, `disparity`, `gift_content`, `gift_from`, `gift_to`, `remote_ip`, `pick_code`, `cron_status`, `created_at`, `updated_at`) VALUES ($id, '1912160000282', '19121600002826', 'paid', 'PAID', NULL, NULL, 0.00, '43', '10000265636', 'Connext Li', NULL, NULL, NULL, NULL, 'address', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '王永乾', '上海市', '上海市', '浦东新区', '世纪大道8号国金二期6楼', '15021165981', '+86', '200135', 'WXP', 'CREATED', '7895231264907287', NULL, NULL, 0, 0.00, NULL, NULL, '', '', 1, 2931.00, 3450.00, 519.00, 0.00, 0, 0.00, '0', 0.00, 519.00, 100.00, 0, 0.00, 0.00, 0.00, 2931.00, 0.00, '', '', '', NULL, NULL, 0, '2019-12-16 15:10:31', '2019-12-16 15:10:46');
";

                        $statement = $pdo->prepare($sql);
                        $statement->execute();
                        $insert_id = $pdo->lastInsertId();
$sql="INSERT INTO `css_ii_order`.`order_goods`(`order_id`, `group_id`, `pdt_id`, `price_type`, `product_type`, `product_options`, `sku_id`, `style_number`, `series`, `section`, `model_number`, `image`, `name`, `option`, `weight`, `original_price`, `price`, `labor_price`, `gold_price`, `discount`, `inventory`, `applied_rule_ids`, `qty_refunded`, `qty_shipped`, `lottery_font`, `content`, `pay_time`, `status`, `assr_status`, `oms_status`, `order_status`, `invc_url`, `policyUrl`, `total`, `pdttotal`, `promotion_type`, `promotion_discount`, `promotion_discount_text`, `promotion_sale`, `promotion_sale_text`, `promotion_point`, `promotion_member`, `coupon`, `offer`, `saleTotal`, `sale_pdt`, `point`, `give_point`, `disparity`, `shipping_id`, `ship_method`, `shipping_time`, `is_gift`, `is_service`, `channel`, `guide`, `store_code`, `is_charme`, `is_presale`, `lineNbr`, `pick_code`, `description`, `refund_amount`, `refund_time`, `created_at`, `updated_at`, `diff_earn_points`) VALUES 
($id, NULL, '88285C-24GG', NULL, 'GF', '', 'CN-10509731', '88285C', 'Charme', '#88285C-24GG', '24GG88285C-36---01-120-G0010*55', 'https://wecassets.chowsangsang.com.cn/miniStore/pdt-detail/24GG88285C-36---01-120-00030_55/24GG88285C-36---01-120-00030_55-THIRD-1.jpg', '「可爱系列」Charme三不猴', 
'".'[{\"key\":\"cstandard\",\"value\":\"\\u4e0d\\u770b\\u7334\",\"name\":\"\\u89c4\\u683c\"}]'."',
 '1.25', 1150.00, 977.00, 0.00, 0.00, 173.00, 1,
 '".'[{\"rule_id\":284,\"discount\":\"173.00\",\"rule_name\":\"12.13 \\u5b9a\\u4ef7\\u9ec4\\u94c2\\u91d11\\u4ef69\\u6298 2\\u4ef685\\u6298\",\"display_name\":\"1\\u4ef69\\u6298 2\\u4ef685\\u6298\",\"sub_type\":\"n_discount\",\"type\":\"product_discount\"}]'."', 
 NULL, NULL, 1, '', '2019-12-19 16:20:36', 'instock', '', 'INSTOCK', NULL, NULL, NULL, 977.00, NULL, 'auto', 0.00, NULL, 0.00, NULL, 0.00, 0.00, NULL, NULL, NULL, 977.00, 0, 34, 0.00, NULL, NULL, NULL, 0, 0, '0', '', NULL, 0, 0, 1867, NULL, NULL, 0.00, NULL, '2019-12-16 15:10:31', '2019-12-19 16:20:36', 0);
";

                        $statement = $pdo->prepare($sql);
                        $statement->execute();
                    }
                    $this->pool->put($pdo);
                });
            }
        });

        $s = microtime(true) - $s;
        echo 'Use ' . $s . 's for ' . self::N . ' queries' . PHP_EOL;
    }




}

new MysqlPoolWrite();