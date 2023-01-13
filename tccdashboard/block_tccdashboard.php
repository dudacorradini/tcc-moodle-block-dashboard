<?php

class block_tccdashboard extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_tccdashboard');
    }

    public function get_content() {
		global $USER, $DB, $COURSE, $PAGE, $OUTPUT, $CFG;
		
		if ($this->content !== null) { return $this->content; }
		$id_course = optional_param('id', 0, PARAM_INT);
		$query_count = 'SELECT count(id) FROM {iassign_allsubmissions}';
		$query_average = 
			"SELECT 
				ROUND(AVG(grade), 2) as avg_grade
			FROM(
				SELECT
					user_grade_fixed.id_user,
					AVG(grade) as grade
				FROM(
					SELECT
						user_ex.userid as id_user,
						IF(ISNULL(user_grade.grade), 0, user_grade.grade)*10 as grade
					FROM(
						SELECT 
						statement_aux.id as iassign_statementid,
						all_subm.userid,
						MAX(all_subm.timecreated) AS timecreated
						FROM (
							SELECT
								statem.id
							FROM
								{iassign_statement} as statem,
								{iassign} as iassign 
							WHERE
								statem.visible = 1 AND
								statem.iassignid = iassign.id AND
								iassign.course = {$id_course}
								) as statement_aux  
						LEFT JOIN
							{iassign_allsubmissions} as all_subm 
						ON 1 = 1
						GROUP BY 1, 2	
			) as user_ex
			LEFT JOIN
				{iassign_allsubmissions} as user_grade
			ON
				user_ex.iassign_statementid = user_grade.iassign_statementid AND
				user_ex.userid = user_grade.userid AND
				user_ex.timecreated = user_grade.timecreated) as user_grade_fixed
			GROUP BY 1) avg_aux;";
	
		$count_class = $DB-> get_record_sql($query_count);
		$average_class = $DB->get_record_sql($query_average);  
	  
		foreach($count_class as $x) $count = $x;
		foreach($average_class as $x) $average = round($x, 2);
	
		$this->content =  new stdClass;
	
		$this->content->text = "
	  		<br> Entre aqui para ver as análises sobre os alunos.
	  		<br> No curso atual, temos {$count} submissões no iTarefa.";
		
		$this->content->text .= "<br> A média atual da turma é de {$average} <br>"; 
		//botão para página do dashboard 
		$parameters = array('id'=>$COURSE->id);
		$url = new moodle_url('/blocks/tccdashboard/view.php', $parameters);
		$this->content->text .= "<br><center>" . $OUTPUT->single_button($url, "Saiba mais") . "</center>";
		return $this->content;
	
	  }
}
