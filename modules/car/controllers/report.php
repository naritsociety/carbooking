<?php
/**
 * @filesource modules/car/controllers/report.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Car\Report;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=car-report
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * รายงานการจอง
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $index = (object) array(
            'status' => $request->request('status')->toInt(),
            'car_status' => Language::get('CAR_BOOKING_STATUS'),
        );
        $index->status = isset($index->car_status[$index->status]) ? $index->status : 1;
        // ข้อความ title bar
        $this->title = Language::get('Book a vehicle').' '.$index->car_status[$index->status];
        // เลือกเมนู
        $this->menu = 'report';
        // สมาชิก
        $login = Login::isMember();
        // สามารถอนุมัติห้องประชุมได้
        if (Login::checkPermission($login, 'can_approve_car')) {
            // แสดงผล
            $section = Html::create('section', array(
                'class' => 'content_bg',
            ));
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs',
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-shipping">{LNG_Vehicle}</span></li>');
            $ul->appendChild('<li><span>{LNG_Report}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-report">'.$this->title.'</h2>',
            ));
            // menu
            $section->appendChild(\Index\Tabmenus\View::render($request, 'report', 'car'));
            // แสดงตาราง
            $section->appendChild(createClass('Car\Report\View')->render($request, $index));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
