<?php
/**
 * @filesource modules/car/views/report.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Car\Report;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;

/**
 * module=car-report
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var Object
     */
    private $chauffeur;

    /**
     * ตารางรายการจอง
     *
     * @param Request $request
     * @param object  $index
     *
     * @return string
     */
    public function render(Request $request, $index)
    {
        // ค่าที่ส่งมา
        $params = array(
            'vehicle_id' => $request->request('vehicle_id')->toInt(),
            'chauffeur' => $request->request('chauffeur', -2)->toInt(),
            'status' => $index->status,
        );
        // พนักงานกับรถ
        $this->chauffeur = array(-1 => '{LNG_Self drive}', 0 => '{LNG_Not specified (anyone)}')+\Car\Chauffeur\Model::init()->toSelect();
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Car\Report\Model::toDataTable($params),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('carReport_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('carReport_sort', 'today,create_date DESC')->toString(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'today', 'remain'),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('name', 'contact', 'phone'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/car/model/report/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => array(
                array(
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => array(
                        'delete' => '{LNG_Delete}',
                    ),
                ),
            ),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => array(
                array(
                    'name' => 'vehicle_id',
                    'text' => '{LNG_Vehicle}',
                    'options' => array(0 => '{LNG_all items}')+\Car\Vehicles\Model::toSelect(),
                    'value' => $params['vehicle_id'],
                ),
                array(
                    'name' => 'chauffeur',
                    'text' => '{LNG_Chauffeur}',
                    'options' => array(-2 => '{LNG_all items}') + $this->chauffeur,
                    'value' => $params['chauffeur'],
                ),
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'detail' => array(
                    'text' => '{LNG_Vehicle usage details}',
                ),
                'vehicle_id' => array(
                    'text' => '{LNG_Image}',
                    'class' => 'center',
                ),
                'number' => array(
                    'text' => '{LNG_Vehicle number}',
                    'sort' => 'number',
                ),
                'contact' => array(
                    'text' => '{LNG_Contact name}',
                ),
                'phone' => array(
                    'text' => '{LNG_Phone}',
                    'class' => 'center',
                ),
                'begin' => array(
                    'text' => '{LNG_Date}',
                    'class' => 'center',
                    'sort' => 'begin',
                ),
                'end' => array(
                    'text' => '{LNG_time}',
                    'class' => 'center',
                ),
                'chauffeur' => array(
                    'text' => '{LNG_Chauffeur}',
                    'class' => 'center',
                    'sort' => 'chauffeur',
                ),
                'create_date' => array(
                    'text' => '{LNG_Created}',
                    'class' => 'center',
                    'sort' => 'create_date',
                ),
                'reason' => array(
                    'text' => '{LNG_Reason}',
                ),
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'vehicle_id' => array(
                    'class' => 'center',
                ),
                'phone' => array(
                    'class' => 'center',
                ),
                'begin' => array(
                    'class' => 'center',
                ),
                'end' => array(
                    'class' => 'center',
                ),
                'chauffeur' => array(
                    'class' => 'center',
                ),
                'create_date' => array(
                    'class' => 'center',
                ),
            ),
            /* ฟังก์ชั่นตรวจสอบการแสดงผลปุ่มในแถว */
            'onCreateButton' => array($this, 'onCreateButton'),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'car-order', 'id' => ':id')),
                    'text' => '{LNG_Edit}',
                ),
            ),
        ));
        // save cookie
        setcookie('carReport_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        setcookie('carReport_sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        if ($item['today'] == 1) {
            $prop->class = 'bg3';
        }
        $thumb = is_file(ROOT_PATH.DATA_FOLDER.'car/'.$item['vehicle_id'].'.jpg') ? WEB_URL.DATA_FOLDER.'car/'.$item['vehicle_id'].'.jpg' : WEB_URL.'modules/car/img/noimage.png';
        $item['vehicle_id'] = '<img src="'.$thumb.'" style="max-height:50px;max-width:50px" alt=thumbnail>';
        $item['phone'] = '<a href="tel:'.$item['phone'].'">'.$item['phone'].'</a>';
        list($begin_date, $begin_time) = explode(' ', $item['begin']);
        list($end_date, $end_time) = explode(' ', $item['end']);
        $item['begin'] = Date::format($begin_date, 'd M Y');
        if ($begin_date != $end_date) {
            $item['begin'] .= '-'.Date::format($end_date, 'd M Y');
        }
        $item['end'] = Date::format($begin_time, 'H:i').'-'.Date::format($end_time, 'H:i');
        $item['create_date'] = Date::format($item['create_date']);
        $item['chauffeur'] = isset($this->chauffeur[$item['chauffeur']]) ? $this->chauffeur[$item['chauffeur']] : '';
        return $item;
    }

    /**
     * ฟังกชั่นตรวจสอบว่าสามารถสร้างปุ่มได้หรือไม่
     *
     * @param array $item
     *
     * @return array
     */
    public function onCreateButton($btn, $attributes, $item)
    {
        if ($btn == 'edit') {
            if (empty(self::$cfg->car_approving) && $item['today'] == 2) {
                return false;
            } elseif (self::$cfg->car_approving == 1 && $item['remain'] < 0) {
                return false;
            } else {
                return $attributes;
            }
        } else {
            return $attributes;
        }
    }
}
