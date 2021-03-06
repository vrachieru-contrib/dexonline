<?php

class AccuracyProject extends BaseObject implements DatedObject {
  static $_table = 'AccuracyProject';

  const METHOD_NEWEST = 0;
  const METHOD_OLDEST = 1;
  const METHOD_RANDOM = 2;
  static $METHOD_NAMES = [
    self::METHOD_NEWEST => 'Descrescător după dată',
    self::METHOD_OLDEST => 'Crescător după dată',
    self::METHOD_RANDOM => 'Ordine aleatorie',
  ];

  private $source = null;
  private $user = null;

  static function getMethodNames() {
    return self::$METHOD_NAMES;
  }

  function getMethodName() {
    return self::$METHOD_NAMES[$this->method];
  }

  function getSource() {
    if ($this->source === null) {
      $this->source = Source::get_by_id($this->sourceId);
    }
    return $this->source;
  }

  function getUser() {
    if ($this->user === null) {
      $this->user = User::get_by_id($this->userId);
    }
    return $this->user;
  }

  function hasStartDate() {
    return $this->startDate && ($this->startDate != '0000-00-00');
  }

  function hasEndDate() {
    return $this->endDate && ($this->endDate != '0000-00-00');
  }

  // Returns a ready-to-run idiorm query
  function getQuery() {
    $q = Model::factory('Definition')
       ->where_in('status', [ Definition::ST_ACTIVE, Definition::ST_HIDDEN ])
       ->where('userId', $this->userId);

    if ($this->sourceId) {
      $q = $q->where('sourceId', $this->sourceId);
    }

    if ($this->hasStartDate()) {
      $ts = strtotime($this->startDate);
      $q = $q->where_gte('createDate', $ts);
    }

    if ($this->hasEndDate()) {
      $ts = strtotime($this->endDate);
      $q = $q->where_lte('createDate', $ts);
    }

    switch ($this->method) {
      case self::METHOD_NEWEST:
        $q = $q->order_by_desc('createDate');
        break;

      case self::METHOD_OLDEST:
        $q = $q->order_by_asc('createDate');
        break;

      case self::METHOD_RANDOM:
        $q = $q->order_by_expr('rand()');
        break;
    }

    return $q;
  }

  // Finds a definition covered by the project that wasn't already evaluated.
  function getDefinition() {
    return $this->getQuery()
      ->where_raw("id not in (select definitionId from AccuracyRecord)")
      ->find_one();
  }

  // Returns an array of (id, lexicon) for all evaluated definitions.
  function getDefinitionData() {
    $data = Model::factory('Definition')
          ->table_alias('d')
          ->select('d.id')
          ->select('d.lexicon')
          ->join('AccuracyRecord', [ 'd.id', '=', 'ar.definitionId'], 'ar')
          ->where('ar.projectId', $this->id)
          ->order_by_desc('ar.createDate')
          ->find_array();
    return $data;
  }

  // Returns accuracy results based on the definitions evaluated so far.
  function getAccuracyData() {
    $result = [
      'evalCount' => 0,                         // number of evaluated definitions
      'evalLength' => 0,                        // length of evaluated definitions
      'errors' => 0,                            // number of errors
    ];
    $data = Model::factory('Definition')
          ->table_alias('d')
          ->select_expr('char_length(d.internalRep)', 'len')
          ->select('ar.errors')
          ->join('AccuracyRecord', [ 'd.id', '=', 'ar.definitionId'], 'ar')
          ->where('ar.projectId', $this->id)
          ->find_many();
    foreach ($data as $row) {
      $result['evalCount']++;
      $result['evalLength'] += $row->len;
      $result['errors'] += $row->errors;
    }

     // number of matching definitions
    $result['defCount'] = $this->getQuery()->count();

     // accuracy (fraction of correct characters expressed as percentage)
    $result['accuracy'] = $result['evalLength']
                        ? (1 - $result['errors'] / $result['evalLength']) * 100
                        : 0;
    $result['errorRate'] = $result['evalLength']
                         ? $result['errors'] / $result['evalLength'] * 1000
                         : 0;

    return $result;
  }

  // Validates the project. Sets flash errors if needed. Returns true on success.
  function validate() {
    if (!$this->name) {
      FlashMessage::add('Numele nu poate fi vid.');
    }
    if (!$this->userId) {
      FlashMessage::add('Utilizatorul nu poate fi vid.');
    }
    if ($this->startDate && !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $this->startDate)) {
      FlashMessage::add('Data de început trebuie să aibă formatul AAAA-LL-ZZ');
    }
    if ($this->endDate && !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $this->endDate)) {
      FlashMessage::add('Data de sfârșit trebuie să aibă formatul AAAA-LL-ZZ');
    }

    // Count the characters in all the applicable definitions
    $count = $this->getQuery()->count();
    if ($count <= 100) {
      FlashMessage::add("Criteriile alese returnează doar {$count} definiții. " .
                        "Relaxați-le pentru a obține minim 100 de definiții.");
    }

    return empty(FlashMessage::getMessages());
  }


  function __toString() {
    $result = "{$this->name} (";

    $user = User::get_by_id($this->userId);
    $result .= $user->nick;

    if ($this->sourceId) {
      $source = Source::get_by_id($this->sourceId);
      $result .= ", {$source->shortName}";
    }

    if ($this->hasStartDate()) {
      $result .= ", de la {$this->startDate}";
    }

    if ($this->hasEndDate()) {
      $result .= ", până la {$this->endDate}";
    }

    $result .= ")";

    return $result;
  }

  function delete() {
    AccuracyRecord::delete_all_by_projectId($this->id);
    parent::delete();
  }
}

?>
