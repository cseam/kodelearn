<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Lecture extends Controller_Base {
    
    public function action_index(){
        
        if($this->request->param('sort')){
            $sort = $this->request->param('sort');
        } else {
            $sort = 'name';
        }
        
        if($this->request->param('order')){
            $order = $this->request->param('order');
        } else {
            $order = 'DESC';
        }
        
        $lecture = ORM::factory('lecture')
                         ->join('courses')
                         ->on('courses.id', '=', 'lectures.course_id');
        
        $count = $lecture->count_all();
        
        $pagination = Pagination::factory(array(
            'total_items'    => $count,
            'items_per_page' => 5,
        ));
        
        $lecture->select(array('courses.name','course_name'), 'firstname', 'lastname')->join('courses')
            ->on('courses.id', '=', 'lectures.course_id')
            ->join('users')
            ->on('users.id', '=', 'lectures.user_id')
            ->group_by('id')
            ->order_by($sort, $order)
            ->limit($pagination->items_per_page)
            ->offset($pagination->offset)
            ;
        $lectures = $lecture->find_all();
        
        $sorting = new Sort(array(
            'Lecture'           => 'name',
            'Course'            => 'courses.name',
            'Lecturer'          => 'firstname',
            'When'              => '',
            'Action'            => ''
        ));
        
        $url = ('lecture/index');
        
        $sorting->set_link($url);
        
        $sorting->set_order($order);
        $sorting->set_sort($sort);
        $heading = $sorting->render();
        
        $table = array('heading' => $heading, 'data' => $lectures);
        
        // Render the pagination links
        $pagination = $pagination->render();
        
        $links = array(
            'add'       => Html::anchor('/lecture/add/', 'Create a Lecture', array('class' => 'createButton l')),
            'delete'    => URL::site('/exam/delete/'),
        );
        
        $view = View::factory('lecture/list')
            ->bind('links', $links)
            ->bind('table', $table)
            ->bind('count', $count)
            ->bind('pagination', $pagination);
    	    	
        Breadcrumbs::add(array(
            'Lectures', Url::site('lecture')
        ));
    	
        $this->content = $view;
    }
    
    public function action_add(){
    	
        $submitted = FALSE;
        
        if($this->request->method() === 'POST' && $this->request->post()){
            if (Arr::get($this->request->post(), 'save') !== null){
                
            	$data = Stickyform::ungroup_params(($this->request->post()));
            	
                $submitted = true;
                if ($this->validate($data)) {
	                if($this->request->post('type') == 'once'){
	                    $data['when'] = '';
	                    $data['start_date'] = strtotime($data['once_date']) + ($data['once_from'] * 60);
	                    $data['end_date'] = strtotime($data['once_date']) + ($data['once_to'] * 60);
	                    
	                } else {
	                	$date_range = $this->request->post('repeat');
	                    $data['start_date'] = strtotime($date_range['from']);
	                    $data['end_date'] = strtotime($date_range['to']) ;
	                	
	                	$days = array();
	                	foreach($this->request->post('days') as $day=>$value){
	                		$time = $this->request->post(strtolower($day));
	                		$days[$day] = $time['from'] . ':' . $time['to'];
	                	}
	                	
	                    $data['when'] = serialize($days);
	                }
	                
	                $event_lecture = new Event_Lecture();
	                $event_lecture->set_values(array_merge($this->request->post(), $data));
	                $event_lecture->add();
	                Request::current()->redirect('lecture');
	                exit;
                } 
            }
        }
        
        $form = $this->form('lecture/add', $submitted);

        $days = array();
        
        $slider = array(
            'once_slider' => array('from' => 600, 'to' => 800),
            'monday_slider' => array('from' => 600, 'to' => 800),
            'tuesday_slider' => array('from' => 600, 'to' => 800),
            'wednesday_slider' => array('from' => 600, 'to' => 800),
            'thursday_slider' => array('from' => 600, 'to' => 800),
            'friday_slider' => array('from' => 600, 'to' => 800),
            'saturday_slider' => array('from' => 600, 'to' => 800),
            'sunday_slider' => array('from' => 600, 'to' => 800)
        );
        
        $view = View::factory('lecture/form')
            ->bind('form', $form)
            ->bind('errors', $this->_errors)
            ->bind('slider', $slider)
            ->bind('days', $days);
        
        Breadcrumbs::add(array(
            'Lectures', Url::site('lecture')
        ));
            
        Breadcrumbs::add(array(
            'Create', Url::site('lecture/add')
        ));
            
        $this->content = $view;
    	
    }
    
    private function validate($data){
    	$lecture = ORM::factory('lecture');
        $validator = $lecture->validator($data);
        if($this->request->post('type') == 'once'){
            $validator->rule('once_date', 'not_empty')
                      ->rule('once_date', 'date');
        } else {
            $validator->rule('repeat_from', 'not_empty')
                      ->rule('repeat_from', 'date')
                      ->rule('repeat_to', 'not_empty')
                      ->rule('repeat_to', 'date')
                      ->rule('repeat_from', 'Model_Lecture::date_check', array(':value',$data['repeat_to']));
        }
        
        if($validator->check()){
        	if($this->request->post('type') == 'repeat' AND (!$this->request->post('days'))){
        		$this->_errors = array('days' => 'Please select atleast one day');
        		return false;
        	} 
            return true;	
        } else {
            $this->_errors = $validator->errors('lecture');
            return false;
        }
        
    }

    private function form($action, $submitted = false, $saved_data = array()){
        
        $courses = ORM::factory('course')->find_all()->as_array('id', 'name');

        $users = array();
        foreach(Model_Role::get_users('teacher') as $user){
            $users[$user->id] = $user->firstname . ' ' . $user->lastname;
        }
        
        $rooms = array();
        foreach(ORM::factory('room')->find_all() as $room){
        	$rooms[$room->id] = $room->room_number . ', ' . $room->room_name;
        }

        $form = new Stickyform($action, array(), ($submitted ? $this->_errors : array()));
        $form->default_data = array(
            'name'          => '',
            'user_id'       => '',
            'course_id'     => '',
            'room_id'       => '',
            'once_date'     => '',
            'repeat_from'   => '',
            'repeat_to'     => '',
            'type'          => 'once',
        );
        
        $form->saved_data = $saved_data;
        $form->posted_data = $submitted ? Stickyform::ungroup_params(($this->request->post())) : array();
        $form->append('Name', 'name', 'text');
        $form->append('Type', 'type', 'radio');
        $form->append('Date:', 'once_date', 'text', array('attributes' => array('class' => 'date', 'name' => 'once[date]')));
        $form->append('From:', 'repeat_from', 'text', array('attributes' => array('class' => 'date', 'name' => 'repeat[from]')));
        $form->append('To:', 'repeat_to', 'text', array('attributes' => array('class' => 'date', 'name' => 'repeat[to]')));
        $form->append('Lecturer', 'user_id', 'select', array('options' => $users));
        $form->append('Course', 'course_id', 'select', array('options' => $courses));
        $form->append('Room', 'room_id', 'select', array('options' => $rooms));
        $form->append('Save', 'save', 'submit', array('attributes' => array('class' => 'button')));
        $form->process();
        
        return $form;
        
    }
    
    public function action_edit(){
    	
    	$id = $this->request->param('id');
    	if(!$id)
    	   Request::current()->redirect('lecture');
        
        $lecture = ORM::factory('lecture', $id);
        $submitted = FALSE;
        
        if($this->request->method() === 'POST' && $this->request->post()){
            if (Arr::get($this->request->post(), 'save') !== null){
                
                $data = Stickyform::ungroup_params(($this->request->post()));
                
                $submitted = true;
                if ($this->validate($data)) {
                    if($this->request->post('type') == 'once'){
                        $data['when'] = '';
                        $data['start_date'] = strtotime($data['once_date']) + ($data['once_from'] * 60);
                        $data['end_date'] = strtotime($data['once_date']) + ($data['once_to'] * 60);
                        
                    } else {
                        $date_range = $this->request->post('repeat');
                        $data['start_date'] = strtotime($date_range['from']);
                        $data['end_date'] = strtotime($date_range['to']) ;
                        
                        $days = array();
                        foreach($this->request->post('days') as $day=>$value){
                            $time = $this->request->post(strtolower($day));
                            $days[$day] = $time['from'] . ':' . $time['to'];
                        }
                        
                        $data['when'] = serialize($days);
                    }
                    
                    $event_lecture = new Event_Lecture();
                    $event_lecture->set_values(array_merge($this->request->post(), $data));
                    $event_lecture->update($id);
                    Request::current()->redirect('lecture');
                    exit;
                } 
            }
        }
        
        $slider = array(
            'once_slider' => array('from' => 600, 'to' => 800),
            'monday_slider' => array('from' => 600, 'to' => 800),
            'tuesday_slider' => array('from' => 600, 'to' => 800),
            'wednesday_slider' => array('from' => 600, 'to' => 800),
            'thursday_slider' => array('from' => 600, 'to' => 800),
            'friday_slider' => array('from' => 600, 'to' => 800),
            'saturday_slider' => array('from' => 600, 'to' => 800),
            'sunday_slider' => array('from' => 600, 'to' => 800)
        );
        
        $saved_data = array(
            'name'          => $lecture->name,
            'user_id'       => $lecture->user_id,
            'course_id'     => $lecture->course_id,
            'room_id'       => $lecture->room_id,
            'type'          => $lecture->type
        );
        
        if($lecture->type == 'once'){
	        $saved_data = array_merge(array(
	            'once_date'     => date('Y-m-d', $lecture->start_date),
	            'repeat_from'   => '',
	            'repeat_to'     => '',
	        ) , $saved_data);
	        $days = array();
	        
	        $saved_slider = array('once_slider' => array(
	           'from'  => ($lecture->start_date - strtotime(date('Y-m-d', $lecture->start_date))) / 60,
	           'to'    => ($lecture->end_date - strtotime(date('Y-m-d', $lecture->end_date))) / 60
	        ));
        } else {
        	$saved_data = array_merge(array(
                'once_date'     => '',
                'repeat_from'   => date('Y-m-d', $lecture->start_date),
                'repeat_to'     => date('Y-m-d', $lecture->end_date),
            ) , $saved_data);
            
            $days = unserialize($lecture->when);
            
            $saved_slider = array();
            foreach($days as $day=>$time){
            	$range = explode(':', $time);
            	$saved_slider[strtolower($day) . '_slider'] = array('from' => $range[0], 'to' => $range[1] );
            }
        }
        
        $form = $this->form('lecture/edit/id/' . $id, $submitted, $saved_data);

        $slider = array_merge($slider, $saved_slider);
        
        $view = View::factory('lecture/form')
            ->bind('form', $form)
            ->bind('slider', $slider)
            ->bind('days', $days);
        
        Breadcrumbs::add(array(
            'Lectures', Url::site('lecture')
        ));
            
        Breadcrumbs::add(array(
            'Edit', Url::site('lecture/edit/id/' . $id)
        ));
            
        $this->content = $view;        
    }
}
