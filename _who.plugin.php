<?php

class who_plugin extends Plugin
{
  var $name = 'Who';
  var $version = '1.0';

  var $code = 'whoplug';
  var $group = 'antispam';
  var $number_of_installs = 1;

  var $apply_rendering = 'never';

  private function getInput()
	{
    echo '<div class="label">';
    echo '<label for="nomo_de_verkisto">' . $this->T_('Anti-spam:  ') . '</label></div>';
    echo '<div class="input"><input type="text" name="nomo_de_verkisto" id="nomo_de_verkisto" size="40" maxlength="100" />';
    echo '<span class="note">(' . $this->T_('The name of who wrote this entry') . ')</span>';
		echo '</div>';
  }

  function BeforeCommentFormInsert(& $params)
  {
    $comment =& $params['Comment'];
    $is_preview = $params['is_preview'];
    $author = $comment->Item->get_creator_User()->firstname;
    header('Content-type: text/html; charset=utf-8');

    if (!$is_preview && !is_logged_in() && (strtolower($_POST['nomo_de_verkisto']) != strtolower($author)))
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
}

?>
