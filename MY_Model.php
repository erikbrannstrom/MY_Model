<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * MY_Model Class
 *
 * Model containing some basic functionality.
 *
 * @category	Libraries
 * @author		Erik Brännström
 */
class MY_Model extends Model {

    /**
     * Name of primary key field(s) in table.
     * Use an array for compound keys.
     */
    protected $primary_key = 'id';
    /**
     * All fields in the table, along with the human field name
     * and the validation rules.
     *
     * E.g.	array( array('username', 'username', 'required|alpha')
     */
    protected $fields = array();
    /**
     * Name of the table.
     */
    protected $table;
    /**
     * Remember if data has been validated.
     */
    private $_validated = false;

    /**
     * PHP4 constructor, see __construct.
     */
    public function MY_Model() {
        $this->__construct();
    }

    /**
     * Loads parent constructor and validation library.
     */
    public function __construct() {
        parent::Model();
        if (!isset($this->form_validation))
            $this->load->library('form_validation');
    }

    /**
     * Perform full validation on all fields.
     */
    function validate($data = null) {
        if ($data) {
            $old_post = $_POST;
            $this->_set_post($data);
        }
        foreach ($this->fields as $field) {
            $this->form_validation->set_rules($field[0], $field[1], $field[2]);
        }
        $this->_validated = $this->form_validation->run();

        if ($data)
            $_POST = $old_post;
        return $this->_validated;
    }

    /**
     * Perform validation of data. Ignores data fields that
     * are not sent to the method. Useful for single field
     * validation etc.
     */
    function validateData($data = null) {
        if ($data) {
            // Replace POST with data for validation
            $old_post = $_POST;
            $this->_set_post($data);
        }

        foreach ($this->fields as $field) {
            if ($this->input->post($field[0]) !== false) {
                $this->form_validation->set_rules($field[0], $field[1], $field[2]);
            }
        }

        $this->_validated = $this->form_validation->run();

        if ($data) {
            // Reset original POST data
            $_POST = $old_post;
        }

        return $this->_validated;
    }

    /**
     * Create one new row in the table.
     */
    function create($data = null, $post = true) {
        if ($post) { // Use data from POST
            foreach ($this->fields as $field) {
                $value = $this->input->post($field[0]);
                if ($value !== false && !empty($value)) {
                    $this->db->set($field[0], $value);
                }
            }
        }

        if ($data) { // Use data from parameter
            foreach ($data as $key => $value) {
                if (!empty($value))
                    $this->db->set($key, $value);
            }
        }

        $this->db->insert($this->table);
        return $this->db->insert_id();
    }

    /**
     * Get data from the table.
     */
    function read($where = null, $limit = 0) {
        if ($where != null) // Set where clause, else get all
            $this->_set_where($where);
        if ($limit > 0)
            $this->db->limit($limit);
        return $this->db->get($this->table)->result();
    }

    /**
     * Get one row from the table. If where clause matches
     * multiple rows, the first one will be returned.
     */
    function fetchOne($where) {
        $this->_set_where($where);
        return $this->db->get($this->table, 1)->row();
    }

    /**
     * Update all table rows matching where clause to the POST
     * and/or data array.
     */
    function update($where, $data = null, $post = true) {
        $this->_set_where($where);

        if ($post) { // Use data from POST
            foreach ($this->fields as $field) {
                if ($this->input->post($field[0]) !== false) {
                    $this->db->set($field[0], $this->input->post($field[0]));
                }
            }
        }

        if ($data) { // Use data from parameter
            foreach ($data as $key => $value) {
                $this->db->set($key, $value);
            }
        }

        $this->db->update($this->table);
    }

    /**
     * Delete all table rows matching where clause.
     */
    function delete($where) {
        $this->_set_where($where);
        $this->db->delete($this->table);
        return $this->db->affected_rows();
    }

    /**
     * Delete only one table row matching where clause.
     */
    function deleteOne($where) {
        $this->_set_where($where);
        $this->db->limit(1)
                ->delete($this->table);
    }

    /**
     * Used internally to set where clause depending
     * on the input. The cases that are handled are the following:
     *
     * 	- One primary key value, e.g. 1 for primary key 'id'
     * 	- Associative array, e.g. array('username' => 'admin')
     * 	- Compound key values, e.g. array(1, 'admin') for primary array('id', 'username')
     * 	- Multiple primary values, e.g. array(1, 2, 3) for primary key 'id'
     */
    protected function _set_where($where) {
        if (!is_array($where)) { // Primary key value(s)
            $this->db->where($this->primary_key, $where);
        } else if (count($where) > 0) {
            if (count(array_diff_key($where, array_keys(array_keys($where)))) !== 0) {
                // Associative array, custom where
                foreach ($where as $field => $value) {
                    if (!empty($value))
                        $this->db->where($field, $value);
                    else
                        $this->db->where($field, null);
                }
            } else if (is_array($this->primary_key) && count($this->primary_key) == count($where)) {
                // Compound key values
                foreach ($this->primary_key as $i => $key) {
                    $this->db->where($key, $where[$i]);
                }
            } else if (!is_array($this->primary_key)) {
                // Multiple values in where, meaning or where
                foreach ($where as $value) {
                    $this->db->or_where($this->primary_key, $value);
                }
            }
        }
    }

    /**
     * Helper method to clear post and set all values to those specified.
     */
    private function _set_post($data) {
        $_POST = array();
        foreach ($data as $key => $value)
            $_POST[$key] = $value;
    }

}