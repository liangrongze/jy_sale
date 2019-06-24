<?php

namespace App\Library;

use App\Models\Fee;
use App\Models\Tor;
use App\Models\Users;
use Illuminate\Support\Facades\DB;

define('VIP_GOODS', 209);//VIP
define('ZX_GOODS', 210);//尊享
define('HH_GOODS', 211);//豪华
define('ZZ_GOODS', 212);//至尊
define('FG_GOODS', 213);//复购

define('VIP_RANK', 4);//NBW_VIP会员（rank_id:4）
define('ZX_RANK', 3);//NBW_尊享会员（rank_id:3）
define('GJ_RANK', 2);//NBW_高级经销商（rank_id:2）
define('ZJX_RANK', 1);//NBW_总经销（rank_id:1，要注意只有此级有rank_exid，且rank_exid=1时计算比例不同）

class Calculator
{
    protected $order;
    protected $jytor_id;
    protected $init_sql = [
        //重置所有测试用户角色为0
        'sql1' => 'UPDATE ecs_users SET jy_rank_id=0, jy_rank_exid=0 WHERE user_id IN (2,13,5, 14, 468,15,171,17,258, 259, 260, 322,324)',
        //升级uid=2的用户为总经销方案3，rank_id=1,exid=3
        'sql2' => 'UPDATE ecs_users SET jy_rank_id=1, jy_rank_exid=3 WHERE user_id IN (2)',
        //升级uid=2的用户为总经销非方案3，rank_id=1,exid=3
        'sql21' => 'UPDATE ecs_users SET jy_rank_id=1, jy_rank_exid=0 WHERE user_id IN (2)',
        //升级uid=5的用户为总销商方案3，rank_id=1,exid=3
        'sql3' => 'UPDATE ecs_users SET jy_rank_id=1, jy_rank_exid=3 WHERE user_id IN (5)',
        //升级uid=5的用户为总销商非方案3，rank_id=1,exid=0
        'sql4' => ' UPDATE ecs_users SET jy_rank_id=1, jy_rank_exid=3 WHERE user_id IN (5);',
        //升级uid=468的用户为高级经销商，rank_id=2,exid=0
        'sql42' => 'UPDATE ecs_users SET jy_rank_id=2, jy_rank_exid=0 WHERE user_id IN (5)',
        //升级uid=468的用户为尊享会员，rank_id=3,exid=0
        'sql43' => 'UPDATE ecs_users SET jy_rank_id=2, jy_rank_exid=0 WHERE user_id IN (5)',
        //升级uid=468的用户为VIP会员，rank_id=4,exid=0
        'sql44' => 'UPDATE ecs_users SET jy_rank_id=2, jy_rank_exid=0 WHERE user_id IN (5)',
        //sql45、升级uid=5的用户为注册用户，rank_id=0,exid=0
        'sql45' =>'UPDATE ecs_users SET jy_rank_id=2, jy_rank_exid=0 WHERE user_id IN (5)',
        //升级uid=468的用户为总销商方案3，rank_id=1,exid=3
        'sql5' => 'UPDATE ecs_users SET jy_rank_id=1, jy_rank_exid=3 WHERE user_id IN (468)',
        //升级uid=468的用户为总销商非方案3，rank_id=1,exid=0
        'sql51' => 'UPDATE ecs_users SET jy_rank_id=1, jy_rank_exid=0 WHERE user_id IN (468)',
        //升级uid=468的用户为高级经销商，rank_id=2,exid=0
        'sql6' => 'UPDATE ecs_users SET jy_rank_id=2, jy_rank_exid=0 WHERE user_id IN (468)',
        //升级uid=468的用户为尊享会员，rank_id=3,exid=0
        'sql7' => 'UPDATE ecs_users SET jy_rank_id=3, jy_rank_exid=0 WHERE user_id IN (468)',
        //升级uid=468的用户为VIP会员，rank_id=4,exid=0
        'sql8' => 'UPDATE ecs_users SET jy_rank_id=4, jy_rank_exid=0 WHERE user_id IN (468)',
    ];

    protected $goods = [
        '209' => ['goods_id' => 209, 'gp_price' => 996, 'rank_id' => 4, 'name' => '敞呼吸VIP套餐',],
        '210' => ['goods_id' => 210, 'gp_price' => 2980, 'rank_id' => 3, 'name' => '敞呼吸尊享套餐',],
        '211' => ['goods_id' => 211, 'gp_price' => 9960, 'rank_id' => 2, 'name' => '敞呼吸豪华套餐',],
        '212' => ['goods_id' => 212, 'gp_price' => 16000, 'rank_id' => 1, 'name' => '敞呼吸至尊套餐',],
        '213' => ['goods_id' => 213, 'gp_price' => 498, 'rank_id' => '?', 'name' => '敞呼吸复购商品',],
    ];

    public function __construct($jytor_id = 0)
    {
        $this->order = Tor::where('jytor_id', $jytor_id)->first();
        $this->order['vip_package'] = $this->goods[209]['gp_price'] * $this->order['gp_quantity'];
        $this->order['zx_package'] = $this->goods[210]['gp_price'] * $this->order['gp_quantity'];
        $this->order['gj_package'] = $this->goods[211]['gp_price'] * $this->order['gp_quantity'];
        $this->order['zjx_package'] = $this->goods[212]['gp_price'] * $this->order['gp_quantity'];
        $remark = explode(',', $this->order['remark']);
        foreach ($remark as $item) {
            $sql = $this->init_sql[$item];
            DB::beginTransaction();
            try {
                DB::update($sql);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
            }

        }
    }

    public function sum()
    {
        if (empty($this->order)) {
            return ['status' => false, 'msg' => 'order is not found', 'code' => 403];
        }
        if(!$this->order['og_order_payed']){
            return ['status' => false, 'msg' => 'order is not paid', 'code' => 403];
        }

        $fee_info = Fee::where('jytor_id', $this->jytor_id)->first();
        if ($fee_info) {
            return ['status' => true, 'msg' => $fee_info->jyfee_id];
        }
        $tjr_info = $this->getUserInfo($this->order['tjr_uid']);
        if ($tjr_info->parent_id == 0) {
            $tjr_parent_info = '';
            $tjr_parent_rank_id = 0;
            $tjr_parent_uid = 0;
            $tjr_parent_rank_exid = 0;
        } else {
            $tjr_parent_info = Users::where('user_id', $tjr_info->parent_id)->first();
            $tjr_parent_rank_id = $tjr_parent_info->jy_rank_id;
            $tjr_parent_uid = $tjr_info->parent_id;
            $tjr_parent_rank_exid = $tjr_parent_info->jy_rank_exid;
        }
        $zjx_user_info = $this->get_zjx_uid($this->order['tjr_uid']);
        $params = [
            'tjr_rank_id' => $this->order['tjr_rank_id'],
            'tjr_uid' => $this->order['tjr_uid'],
            'tjr_rank_exid' => $this->order['tjr_rank_exid'],
            'bjr_rank_id' => $this->order['bjr_rank_id'],
            'bjr_uid' => $this->order['bjr_uid'],
            'tjr_parent_rank_id' => $tjr_parent_rank_id,
            'tjr_parent_uid' => $tjr_parent_uid,
            'tjr_parent_rank_exid' => $tjr_parent_rank_exid,
            'zjx_uid' => isset($zjx_user_info->user_id) ? $zjx_user_info->user_id : 0,
            'zjx_rank_exid' => isset($zjx_user_info->rank_exid) ? $zjx_user_info->rank_exid : 0,
            'bjr_rank_exid' => $this->order['bjr_rank_exid'],
        ];
        $vip_income = $this->VipIncome($params);
        $zx_income = $this->ZxIncome($params);
        $gj_income = $this->GjIncome($params);
        $zjx_income = $this->ZjxIncome($params);
        $all_income_record = array_merge($vip_income, $zx_income, $gj_income, $zjx_income);
//        dd($all_income_record);
        $all_income_record = $this->map_format_amount($all_income_record);
        $total_record = [];
        foreach ($all_income_record as $record) {
            $total_record[$record['rank_id']][] = $record;
        }
        DB::beginTransaction();
        try {
            $tjr_fee = $this->getRoleFee($all_income_record, $this->order['tjr_uid']);
            $tjr_p_fee = $this->getRoleFee($all_income_record, $tjr_parent_uid);
            $tjr_avf = $tjr_fee ? 1 : 0;
            $tjr_p_avf = $tjr_p_fee ? 1 : 0;
            $a_rank_tzvip = isset($total_record[1]) ? array_sum(array_column($total_record[1], 'a_rank_tzvip')) : 0;
            if ($a_rank_tzvip) {
                $total_record[1] = array_values(array_filter($total_record[1], function ($val) {
                    return !isset($val['a_rank_tzvip']);
                }));
                $total_record = array_filter($total_record);
            }
            $insertData = [
                'jytor_id' => $this->order['jytor_id'],
                'a_rank_uid' => isset($total_record[1]) ? $total_record[1][0]['user_id'] : 0,
                'b_rank_uid' => isset($total_record[2]) ? $total_record[2][0]['user_id'] : 0,
                'c_rank_uid' => isset($total_record[3]) ? $total_record[3][0]['user_id'] : 0,
                'd_rank_uid' => isset($total_record[4]) ? $total_record[4][0]['user_id'] : 0,
                'a_rank_fee' => isset($total_record[1]) ? array_sum(array_column($total_record[1], 'fee')) : 0,
                'a_rank_tzvip' => $a_rank_tzvip,
                'b_rank_fee' => isset($total_record[2]) ? array_sum(array_column($total_record[2], 'fee')) : 0,
                'c_rank_fee' => isset($total_record[3]) ? array_sum(array_column($total_record[3], 'fee')) : 0,
                'd_rank_fee' => isset($total_record[4]) ? array_sum(array_column($total_record[4], 'fee')) : 0,
                'a_rank_id' => isset($zjx_user_info) ? $zjx_user_info->jy_rank_id : 0,
                'a_rank_exid' => isset($zjx_user_info) ? $zjx_user_info->jy_rank_exid : 0,
                'tjr_uid' => $this->order['tjr_uid'],
                'tjr_rank_id' => $this->order['tjr_rank_id'],
                'tjr_rank_exid' => $this->order['tjr_rank_exid'],
                'tjr_fee' => $tjr_fee,
                'bjr_uid' => $this->order['bjr_uid'],
                'bjr_rank_id' => $this->order['bjr_rank_id'],
                'bjr_rank_exid' => $this->order['bjr_rank_exid'],
                'bjr_fee' => 0,
                'tjr_p_uid' => $tjr_parent_uid,
                'tjr_p_fee' => $tjr_p_fee,
                'tjr_p_rank_id' => $tjr_parent_rank_id,
                'tjr_p_rank_exid' => $tjr_parent_rank_exid,
                'tjr_p_avf' => $tjr_p_avf,
                'tjr_avf' => $tjr_avf,
                'gp_uid' => 0,
                'gp_fee' => $this->order['gp_allowed'] ? $this->order['gp_price'] * $this->order['gp_quantity'] * 0.01 : 0,
                'gp_allowed' => $this->order['gp_allowed'],
                'created' => date('Y-m-d H:i:s'),
            ];
            $fee_id = DB::table('ecs_jyfee')->insertGetId($insertData);
            foreach ($all_income_record as $value) {
                $record = [
                    'jyfee_id' => $fee_id,
                    'jytor_id' => $this->order['jytor_id'],
                    'user_id' => $value['user_id'],
                    'rank_id' => $value['rank_id'] == 'a_rank_tzvip' ? 0 : $value['rank_id'],
                    'rank_exid' => isset($value['rank_exid']) ? $value['rank_exid'] : 0,
                    'fee' => $value['fee'],
                ];
                $this->feelogSave($record);
            }
            DB::commit();
            return ['status' => true, 'msg' => $fee_id];
        } catch (\Exception $e) {
            echo $e->getMessage();
            DB::rollback();
            return ['status' => false, 'msg' => 'failed'];
        }
    }


    public function get_zjx_uid($uid)
    {
        $user = $this->getUserInfo($uid);

        while ($user->jy_rank_id != ZJX_RANK) {
            if ($user->parent_id == 0) {
                return '';
            }
            $user = $this->getUserInfo($user->parent_id);
        }
        return $user;
    }


    public function VipIncome($params)
    {
        $tjr_rank_id = $params['tjr_rank_id'];
        $bjr_rank_id = $params['bjr_rank_id'];
        if ($tjr_rank_id > $bjr_rank_id || $tjr_rank_id == 0) {
            return [];
        }
        $income_record = [];
        $tjr_rank_id == VIP_RANK && $bjr_rank_id == VIP_RANK && $income_record[] = ['fee' => 0.4 * 0.75 * 0.5 * $this->order['gp_price'] * $this->order['gp_quantity'], 'rank_id' => VIP_RANK, 'user_id' => $params['tjr_uid']];
        $count = Users::where(['parent_id' => $params['tjr_uid'], 'jy_rank_id' => VIP_RANK])->count();
        if ($count == 6) {
            Users::where('user_id', $params['tjr_uid'])->update(['jy_rank_id' => ZX_RANK]);
        }
        return $income_record;
    }

    public function ZxIncome($params)
    {
        $tjr_rank_id = $params['tjr_rank_id'];
        $bjr_rank_id = $params['bjr_rank_id'];
        if ($tjr_rank_id > $bjr_rank_id || $tjr_rank_id == 0) {
            return [];
        }
        $tjr_parent_rank_id = $params['tjr_parent_rank_id'];
        $buy_fee = $this->order['gp_price'] * $this->order['gp_quantity'];
        $income_record = [];
        $tjr_rank_id == ZX_RANK && $bjr_rank_id == ZX_RANK && $income_record[] = ['fee' => 0.4 * 0.75 * $buy_fee, 'rank_id' => ZX_RANK, 'user_id' => $params['tjr_uid']];
        $tjr_parent_rank_id == ZX_RANK && $tjr_rank_id == ZX_RANK && $bjr_rank_id == ZX_RANK && $income_record[] = ['fee' => 0.4 * 0.25 * $buy_fee, 'rank_id' => ZX_RANK, 'user_id' => $params['tjr_parent_uid']];
        $tjr_rank_id == ZX_RANK && $bjr_rank_id == VIP_RANK && $income_record[] = ['fee' => 0.4 * 0.75 * $this->order['vip_package'], 'rank_id' => ZX_RANK, 'user_id' => $params['tjr_uid']];
        $tjr_parent_rank_id == ZX_RANK && $tjr_rank_id == VIP_RANK && $bjr_rank_id == VIP_RANK && $income_record[] = ['fee' => 0.4 * 0.75 * 0.5 * $this->order['vip_package'], 'rank_id' => ZX_RANK, 'user_id' => $params['tjr_parent_uid']];
        $count = Users::where(['parent_id' => $params['tjr_uid'], 'jy_rank_id' => ZX_RANK])->count();
        if ($count == 20) {
            Users::where('user_id', $params['tjr_uid'])->update(['jy_rank_id' => GJ_RANK]);
        }
        return $income_record;
    }

    public function GjIncome($params)
    {
        $tjr_rank_id = $params['tjr_rank_id'];
        $bjr_rank_id = $params['bjr_rank_id'];
        if ($tjr_rank_id > $bjr_rank_id || $tjr_rank_id == 0) {
            return [];
        }
        $tjr_parent_rank_id = $params['tjr_parent_rank_id'];
        $buy_fee = $this->order['gp_price'] * $this->order['gp_quantity'];
        $income_record = [];
        $tjr_rank_id == GJ_RANK && $bjr_rank_id == GJ_RANK && $income_record[] = ['fee' => 0.55 * 0.75 * $buy_fee, 'rank_id' => GJ_RANK, 'user_id' => $params['tjr_uid']];
        $tjr_parent_rank_id == GJ_RANK && $tjr_rank_id == GJ_RANK && $bjr_rank_id == GJ_RANK && $income_record[] = ['fee' => 0.55 * 0.25 * $buy_fee, 'rank_id' => GJ_RANK, 'user_id' => $params['tjr_parent_uid']];
        $tjr_parent_rank_id == GJ_RANK && $tjr_rank_id == ZX_RANK && $bjr_rank_id == ZX_RANK && $income_record[] = ['fee' => 0.55 * 0.25 * $this->order['zx_package'], 'rank_id' => GJ_RANK, 'user_id' => $params['tjr_parent_uid']];
        $tjr_rank_id == GJ_RANK && $bjr_rank_id == ZX_RANK && $income_record[] = ['fee' => 0.55 * 0.75 * $this->order['zx_package'], 'rank_id' => GJ_RANK, 'user_id' => $params['tjr_uid']];
        $tjr_rank_id == GJ_RANK && $bjr_rank_id == VIP_RANK && $income_record[] = ['fee' => 0.55 * 0.75 * $this->order['vip_package'], 'rank_id' => GJ_RANK, 'user_id' => $params['tjr_uid']];
        $tjr_parent_rank_id == GJ_RANK && $tjr_rank_id == VIP_RANK && $bjr_rank_id == VIP_RANK && $income_record[] = ['fee' => 0.55 * 0.75 * $this->order['vip_package'] - 0.4 * 0.75 * 0.5 * $this->order['vip_package'], 'rank_id' => GJ_RANK, 'user_id' => $params['tjr_parent_uid']];
        return $income_record;
    }

    public function ZjxIncome($params)
    {
        $tjr_rank_id = $params['tjr_rank_id'];
        $bjr_rank_id = $params['bjr_rank_id'];
        $income_record = [];
        if ($tjr_rank_id > $bjr_rank_id || $tjr_rank_id == 0) {
            $tjr_rank_id!=0 && $bjr_rank_id == VIP_RANK && $income_record[] = ['fee' => '', 'rank_id' => 1, 'user_id' => $params['zjx_uid'], 'rank_exid' => $params['zjx_rank_exid'], 'a_rank_tzvip' => $this->format_amount(0.4 * 0.25 * 0.5 * $this->order['vip_package'])];
            return $income_record;
        }
        $bjr_rank_exid = $params['bjr_rank_exid'];
        $bjr_rank_exid == 3 && $tjr_rank_id == ZJX_RANK && $bjr_rank_id == ZJX_RANK && $income_record[] = ['fee' => 20000, 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_uid'], 'rank_exid' => 3];
        $tjr_rank_id!=0 && $bjr_rank_id == VIP_RANK && $income_record[] = ['fee' => '', 'rank_id' => 1, 'user_id' => $params['zjx_uid'], 'rank_exid' => $params['zjx_rank_exid'], 'a_rank_tzvip' => $this->format_amount(0.4 * 0.25 * 0.5 * $this->order['vip_package'])];
        $params['tjr_rank_exid'] && $bjr_rank_exid == 3 && $income_record[] = ['fee' => 4000, 'rank_id' => $params['tjr_parent_rank_id'], 'user_id' => $params['tjr_parent_uid']];
        if ($params['tjr_rank_exid'] == 3) {
            $tjr_rank_id == ZJX_RANK && $bjr_rank_id == GJ_RANK && $income_record[] = ['fee' => 0.6 * 0.75 * $this->order['gj_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_uid'], 'rank_exid' => 3];
            $tjr_rank_id == ZJX_RANK && $bjr_rank_id == ZX_RANK && $income_record[] = ['fee' => 0.6 * 0.75 * $this->order['zx_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_uid'], 'rank_exid' => 3];
            $tjr_rank_id == ZJX_RANK && $bjr_rank_id == VIP_RANK && $income_record[] = ['fee' => 0.6 * 0.75 * $this->order['vip_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_uid'], 'rank_exid' => 3];
        } elseif ($params['tjr_rank_exid'] == 2) {
            $tjr_rank_id == ZJX_RANK && $bjr_rank_id == GJ_RANK && $income_record[] = ['fee' => 0.6 * 0.75 * $this->order['gj_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_uid'], 'rank_exid' => 2];
            $tjr_rank_id == ZJX_RANK && $bjr_rank_id == ZX_RANK && $income_record[] = ['fee' => 0.6 * 0.75 * $this->order['zx_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_uid'], 'rank_exid' => 2];
            $tjr_rank_id == ZJX_RANK && $bjr_rank_id == VIP_RANK && $income_record[] = ['fee' => 0.6 * 0.75 * $this->order['vip_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_uid'], 'rank_exid' => 2];
        } elseif ($params['tjr_rank_exid'] == 1) {
            $tjr_rank_id == ZJX_RANK && $bjr_rank_id == GJ_RANK && $income_record[] = ['fee' => 0.55 * 0.75 * $this->order['gj_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_uid'], 'rank_exid' => 1];
            $tjr_rank_id == ZJX_RANK && $bjr_rank_id == ZX_RANK && $income_record[] = ['fee' => 0.4 * 0.75 * $this->order['zx_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_uid'], 'rank_exid' => 1];
            $tjr_rank_id == ZJX_RANK && $bjr_rank_id == VIP_RANK && $income_record[] = ['fee' => 0.4 * 0.75 * 0.5 * $this->order['vip_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_uid'], 'rank_exid' => 1];
        }
        if($params['tjr_parent_rank_exid']){
            if ($params['tjr_parent_rank_exid'] == 3 || $params['tjr_parent_rank_exid'] == 2) {
                $tjr_rank_id == GJ_RANK && $bjr_rank_id == GJ_RANK && $income_record[] = ['fee' => 0.6 * 0.25 * $this->order['gj_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_parent_uid'], 'rank_exid' => $params['tjr_parent_rank_exid']];
                $tjr_rank_id == ZX_RANK && $bjr_rank_id == ZX_RANK && $income_record[] = ['fee' => 0.6 * 0.25 * $this->order['zx_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_parent_uid'], 'rank_exid' => $params['tjr_parent_rank_exid']];
                $tjr_rank_id == VIP_RANK && $bjr_rank_id == VIP_RANK && $income_record[] = ['fee' => 0.6 * 0.75 * 0.5 * $this->order['vip_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_parent_uid'], 'rank_exid' => $params['tjr_parent_rank_exid']];
            } elseif ($params['tjr_parent_rank_exid'] == 1) {
                $tjr_rank_id == GJ_RANK && $bjr_rank_id == GJ_RANK && $income_record[] = ['fee' => 0.55 * 0.25 * $this->order['gj_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_parent_uid'], 'rank_exid' => $params['tjr_parent_rank_exid']];
                $tjr_rank_id == ZX_RANK && $bjr_rank_id == ZX_RANK && $income_record[] = ['fee' => 0.4 * 0.25 * $this->order['zx_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_parent_uid'], 'rank_exid' => $params['tjr_parent_rank_exid']];
                $tjr_rank_id == VIP_RANK && $bjr_rank_id == VIP_RANK && $income_record[] = ['fee' => 0.4 * 0.75 * 0.5 * $this->order['vip_package'], 'rank_id' => ZJX_RANK, 'user_id' => $params['tjr_parent_uid'], 'rank_exid' => $params['tjr_parent_rank_exid']];
            }
        }
        return $income_record;
    }

    /**
     * @param $params
     * @return bool
     */
    private function feelogSave($params)
    {
        $insertData = [
            'jyfee_id' => $params['jyfee_id'],
            'jytor_id' => $params['jytor_id'],
            'user_id' => $params['user_id'],
            'rank_id' => $params['rank_id'],
            'rank_exid' => $params['rank_exid'],
            'fee' => $params['fee'],
            'created' => date('Y-m-d H:i:s'),
        ];
        DB::table('ecs_jyfeelog')->insertGetId($insertData);
    }

    private function getRoleFee($income_record, $user_id)
    {
        return array_sum(array_column(array_filter($income_record, function ($val) use ($user_id) {
            if ($val['user_id'] == $user_id) {
                return $val;
            }
        }), 'fee'));
    }

    private function getUserInfo($uid)
    {
        if ($uid == 0) {
            return null;
        }

        if ($user = Users::select()->where('user_id', $uid)->first()) {
            return $user;
        }
        return null;
    }


    /***
     * 抹零处理金额
     * @param $amount
     * @return int
     */
    protected function format_amount($amount)
    {
        $amount = round($amount);
        $length = strlen($amount);
        if ($length == 1) {
            return $amount;
        }
        if ($length == 2) {
            return $amount - $amount % 10;
        }
        $amount_copy = pow(10, $length - 2);
        return $amount - $amount % $amount_copy;
    }

    public function map_format_amount($record)
    {
        return array_map(function ($val) {
            $val['fee'] = $this->format_amount($val['fee']);
            return $val;
        }, $record);
    }
}
