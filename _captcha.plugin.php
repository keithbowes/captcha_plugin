<?php

class captcha_plugin extends Plugin
{
	var $name = 'Captcha plugin';
	var $version = '1.0';

	var $code = 'captcha_plugin';
	var $group = 'antispam';
	var $number_of_installs = 1;

	var $apply_rendering = 'never';

	function PluginInit()
	{
		$this->short_desc = $this->T_('Simple text questions');
		$this->long_desc = $this->T_('Reduces spam by asking the commenter a question');
	}

	private function getInput()
	{
		$this->parseQuestion($question, $answer);
		$ans = base64_encode(serialize($answer));
		echo '<div class="label">';
		echo '<label for="captcha_antwort">' . $this->T_('Anti-spam:  ') . '</label></div>';
		echo '<div class="input"><input type="text" name="captcha_antwort" id="captcha_antwort" size="40" maxlength="100" />';
		echo '<input type="hidden" name="captcha_frage" value="' . $ans . '" />';
		echo '<span class="note">(' . $question . ')</span>';
		echo '</div>';
	}

	private function getQuestions()
	{
		global $Blog;
		$global_questions = explode("\r\n", $this->Settings->get('globfrag'));
		$local_questions = explode("\r\n", $this->get_coll_setting('ortfrag', $Blog));
		$questions = array_merge($global_questions, $local_questions);
		return $questions;
	}

	private function getQuestion($qno = -1)
	{
		$questions = $this->getQuestions();
		if ($qno < 0)
		{
			$qno = count($questions);
			if ($qno > 1)
				$qno = rand(1, $qno - 1);
		}

		return $questions[$qno];
	}

	private function parseQuestion(& $question, & $answer)
	{
		if (!isset($this->question))
			$this->question = $this->getQuestion();

		preg_match('/^(.+)\s+\|\|(.+)$/', $this->question, $matches);
		list($match, $question, $answer) = $matches;
		$answer = explode('|', $answer);
	}

	function BeforeCommentFormInsert(& $params)
	{
		$frage = unserialize(base64_decode($_POST['captcha_frage']));
		$comment =& $params['Comment'];
		$is_preview = $params['is_preview'];
		header('Content-type: text/html; charset=utf-8');

		foreach ($frage as $answer)
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

			$form->begin_fieldset(NULL, array('id' => 'who_plugin'));
			$this->getInput();
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
