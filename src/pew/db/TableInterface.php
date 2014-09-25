<?php

namespace pew\db;

interface TableInterface
{
    public function find($id);
    public function find_all($where = null);
    public function save($data = null);
    public function delete($id = null);
}
