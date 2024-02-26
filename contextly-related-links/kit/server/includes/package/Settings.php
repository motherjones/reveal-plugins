<?php

class ContextlyKitPackageSettings extends ContextlyKitBase implements ArrayAccess {

  protected $path;

  protected $data;

  public function __construct($kit, $path) {
    parent::__construct($kit);

    $this->path = $path;
  }

  protected function load() {
    if (isset($this->data)) {
      return;
    }

    $this->data = array();
    $data = parse_ini_file($this->path, TRUE);
    if ($data) {
      $this->data = $data;
    }
  }

  /**
   * Whether a offset exists
   *
   * @param mixed $offset
   *   An offset to check for.
   *
   * @return boolean
   *   true on success or false on failure.
   */
  public function offsetExists($offset) {
    $this->load();
    return array_key_exists($offset, $this->data);
  }

  /**
   * Offset to retrieve
   *
   * @param mixed $offset
   *   The offset to retrieve.
   *
   * @return mixed
   *   Can return all value types.
   */
  public function offsetGet($offset) {
    $this->load();
    return $this->data[$offset];
  }

  /**
   * Offset to set
   *
   * @param mixed $offset
   *   The offset to assign the value to.
   * @param mixed $value
   *   The value to set.
   */
  public function offsetSet($offset, $value) {
    $this->throwReadOnly();
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Offset to unset
   * @link http://php.net/manual/en/arrayaccess.offsetunset.php
   *
   * @param mixed $offset <p>
   * The offset to unset.
   * </p>
   *
   * @return void
   */
  public function offsetUnset($offset) {
    $this->throwReadOnly();
  }

  protected function throwReadOnly() {
    throw $this->kit->newException('Package settings are read-only');
  }

}
