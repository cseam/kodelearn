<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Question extends Controller_Base {

    protected $_errors = array();

    protected function breadcrumbs() {
        parent::breadcrumbs();
        $course = ORM::factory('course', Session::instance()->get('course_id'));
        if (!$this->request->is_ajax() && $this->request->is_initial()) {
            Breadcrumbs::add(array('Courses', Url::site('course')));
            Breadcrumbs::add(array(sprintf($course->name), Url::site('course/summary/id/'.$course->id)));        
            Breadcrumbs::add(array('Question Bank', Url::site('question')));
        }
    }

    public function action_index() {
        $view = View::factory('question/list')
            ->bind('table', $table)
            ->bind('total', $total)
            ->bind('links', $links)
            ->bind('success', $success)
            ->bind('filter_url', $filter_url)
            ->bind('filter_type', $filter_type)
            ->bind('types', $types);
        $filter_type = (string) $this->request->param('filter_type', '');
        $course_id = Session::instance()->get('course_id');
        $course = ORM::factory('course', (int)$course_id);
        $criteria = array(
            'filter_type' => $this->request->param('filter_type', ''),
            'course_id' => $course_id
        );
        $questions = Model_Question::get_questions($criteria);
        $total = Model_Question::get_total_question_count($criteria);
        $sortables = new Sort(array(
            'Question' => '',
            'Type' => '',
            'Actions' => '',
        ));
        $headings = $sortables->render();
        $table = array(
            'headings' => $headings,
            'data' => $questions
        );
        $types = array_combine(Model_Question::$TYPES, array_map('ucfirst', Model_Question::$TYPES));
        $filter_url = URL::site('question/index');
        $links = array(
            'add' => Html::anchor('/question/add/', 'Create a question', array('class' => 'createButton l')),
            'delete' => URL::site('/question/delete/'),
        );        
        $success = Session::instance()->get_once('success');
        $this->content = $view;
    }

    public function action_add() {
        $view = View::factory('question/form')
            ->bind('type', $type)
            ->bind('course', $course)
            ->bind('form', $form)
            ->bind('hints', $hints)
            ->bind('error_notif', $error_notif)
            ->bind('solution_form', $solution_form)
            ->bind('solution_tab', $solution_tab);        
        $submitted = false;
        $type = $this->request->param('type');
        $question = Question::factory($type);
        $hints = array();
        $course_id = Session::instance()->get('course_id');
        $course = ORM::factory('course', (int)$course_id);
        if ($this->request->method() === 'POST' && $this->request->post()) {
            $submitted = true;
            if ($question->validate($this->request->post())) {
                // var_dump($this->request->post()); exit;
                $question->save($this->request->post());
                Session::instance()->set('success', 'Question added successfully.');
                Request::current()->redirect('question');
            } else {
                $this->_errors = $question->validation_errors();
                $error_notif = __('Form could not be submitted due to errors. Please check the other tabs for error messages too');
            }
            // stickyness for the hints section
            $post_hints = $this->request->post('hints');
            $hints = $question->process_hints($post_hints);
        }
        $form = $this->form('question/add/type/'.$type, $submitted);
        $solution_form = $question->render_form($this->request->post());
        $solution_tab = $question->solution_tab();
        if (!$hints) {
            $hints[] = array(
                'hint' => '',
                'sort_order' => '',
                'deduction' => '',
            );
        }
        Breadcrumbs::add(array(
            'Add', Url::site('question/add')
        ));
        $this->content = $view;
    }

    public function action_edit() {
        $view = View::factory('question/form')
            ->bind('type', $type)
            ->bind('form', $form)
            ->bind('hints', $hints)
            ->bind('course', $course)
            ->bind('error_notif', $error_notif)
            ->bind('solution_form', $solution_form)
            ->bind('solution_tab', $solution_tab);        
        $submitted = false;
        $question = Question::factory((int) $this->request->param('id'));
        $hints = $question->orm()->hints_as_array();
        $course_id = Session::instance()->get('course_id');
        $course = ORM::factory('course', (int)$course_id);
        if ($this->request->method() === 'POST' && $this->request->post()) {
            $submitted = true;
            if ($question->validate($this->request->post())) {
                $question->save($this->request->post());
                Session::instance()->set('success', 'Question edited successfully.');
                Request::current()->redirect('question');
            } else {
                $this->_errors = $question->validation_errors();
                $error_notif = __('Form could not be submitted due to errors. Please check the other tabs for error messages too');
            }
            // stickyness for the hints section
            $post_hints = $this->request->post('hints');
            $hints = $question->process_hints($post_hints);
        }
        $form = $this->form('question/edit/id/'.$question->orm()->id, $submitted, $question->orm()->as_array());
        $solution_form = $question->render_form($this->request->post());
        $solution_tab = $question->solution_tab();
        if (!$hints) {
            $hints[] = array(
                'hint' => '',
                'sort_order' => '',
                'deduction' => '',
            );
        }
        Breadcrumbs::add(array(
            'Edit', Url::site('question/edit/'.$question->orm()->id)
        ));
        $this->content = $view;
    }

    protected function form($action, $submitted=false, $saved_data=array()) {
        $form = new Stickyform($action, array(), ($submitted ? $this->_errors : array()));
        $form->default_data = array(
            'question' => '',
            'extra' => '',
            'course_id' => Session::instance()->get('course_id')
        );
        $form->saved_data = $saved_data;
        $form->posted_data = $submitted ? $this->request->post() : array();
        $form->append('Question', 'question', 'textarea', array('attributes' => array('class' => 'w70 ht120')));
        $form->append('Extra Info', 'extra', 'textarea', array('attributes' => array('class' => 'w70 ht120')));
        $courses = ORM::factory('course')->find_all()->as_array('id', 'name');
    	$form->append('Course', 'course_id', 'select', array('options' => $courses));
        $form->append('Save', 'save', 'submit', array('attributes' => array('class' => 'button r')));
        $form->process();
        return $form;        
    }

    public function action_preview() {        
        $view = View::factory('question/view')
            ->set('preview', true)
            ->bind('question_partial', $question_partial)
            ->bind('hints', $hints);
        $question_id = $this->request->param('id');
        $ques_obj = Question::factory((int)$question_id);   
        $question_partial = $ques_obj->render_question(true);
        $hints = $ques_obj->orm()->hints_as_array();
        $this->content = $view;
    }

    public function action_preview_overlay() {
        // incase the request is not an ajax request, redirect to the not_found page
        if (!$this->request->is_ajax()) {
            Request::current()->redirect('error/not_found');
        }
        $question_id = $this->request->param('id');
        $ques_obj = Question::factory((int)$question_id);   
        $question_partial = $ques_obj->render_question(true);
        $this->content = $question_partial;
    }

    public function action_delete() {
        if ($this->request->method() === 'POST' && $this->request->post('selected')) {
            $selected = $this->request->post('selected');
            foreach ($selected as $question_id) {
                $question = ORM::factory('question', $question_id);
                $question->delete();
            }
            Session::instance()->set('success', 'Question(s) deleted successfully.');
        }
        Request::current()->redirect('question');
    }
}
