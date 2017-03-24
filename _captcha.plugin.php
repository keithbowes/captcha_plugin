<?php

class captcha_plugin extends Plugin
{
	public $name = 'Captcha plugin';
	public $version = '1.0';

	public $code = 'captcha_plugin';
	public $group = 'antispam';
	public $number_of_installs = 1;

	function PluginInit(& $params)
	{
		$this->short_desc = $this->T_('Simple text questions');
		$this->long_desc = $this->T_('Reduces spam by asking the commenter a question');
	}

	private function getInput($form)
	{
		global $Session;

		$this->parseQuestion($question, $answer);
		$ans = base64_encode(serialize($answer));

		if (is_object($Session))
			$Session->set('captcha_frage', $ans);
		else
			$form->hidden('captcha_frage', $ans);

		$form->input_field(array(
			'class' => 'bComment evo_comment form-control form_text_input',
			'label' => $this->T_('Anti-Spam Question'),
			'name' => 'captcha_antwort',
			'size' => 40,
			'maxlength' => 100,
			'note' => '(' . $question . ')',
		));
	}

	private function getQuestions()
	{
		global $Blog;
		$global_questions = explode("\r\n", $this->Settings->get('globfrag'));
		$local_questions = explode("\r\n", $this->get_coll_setting('ortfrag', $Blog));
		return array_merge($global_questions, $local_questions);
	}

	private function getQuestion($qno = -1)
	{
		$questions = $this->getQuestions();
		$qno = rand(1, count($questions) - 1);
		return $questions[$qno];
	}

	private function parseQuestion(& $question, & $answer)
	{
		if (!isset($this->question))
			$this->question = $this->getQuestion();

		preg_match('/^(.+)\s+\|\|(.+)$/', $this->question, $matches);
		if ($matches)
		{
			list($match, $question, $answer) = $matches;
			$answer = explode('|', $answer);
		}
	}

	function BeforeCommentFormInsert(& $params)
	{
		global $Session;
		if (is_object($Session))
			$frage = unserialize(base64_decode($Session->get('captcha_frage')));
		else
			$frage = unserialize(base64_decode($_POST['captcha_frage']));

		$is_preview = $params['is_preview'];
		header('Content-type: text/html; charset=utf-8');

		foreach ((array) $frage as $answer)
		{
			$is_correct = strcasecmp($answer, $_POST['captcha_antwort']) == 0;
			if ($is_correct) break;
		}

		if (!$is_preview && !is_logged_in() && !$is_correct)
		{
			/* The message type must be 'error' so the operation will be aborted.
			   * Without a type, it defaults to 'note', which won't stop the posting. */
			$this->msg($this->T_('You must provide the correct answer to the anti-spam question.'), 'error');
		}
	}

	function DisplayCommentFormFieldset(& $params)
	{
		if (!is_logged_in())
		{
			$form =& $params['Form'];

			$form->begin_fieldset(NULL, array('id' => 'captcha_plugin'));
			$this->getInput($form);
			$form->end_fieldset();
		}
	}

	function GetDefaultSettings(& $params)
	{
		return array(
			'globfrag' => array(
				'defaultvalue' => '',
				'label' => $this->T_('Questions for all collections'),
				'note' => $this->T_('Format: Question? ||Answer1|Answer2|...'),
				'type' => 'html_textarea',
			)
		);
	}

	function get_coll_setting_definitions( & $params )
	{
		return array(
			'ortfrag' => array(
				'defaultvalue' => '',
				'label' => $this->T_('Questions for this collection only'),
				'note' => $this->T_('Format: Question? ||Answer1|Answer2|...'),
				'type' => 'html_textarea',
			  )
		  );
	}
}

?>