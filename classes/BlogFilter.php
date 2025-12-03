<?php
/**
 * Blog Filter Management Class
 */

class BlogFilter {
    private $db;
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    public function getAllFilters() {
        $sql = "SELECT * FROM blog_filter ORDER BY filter ASC";
        return $this->db->fetchAll($sql);
    }
    
    public function getActiveFilters() {
        $sql = "SELECT * FROM blog_filter WHERE is_show = 1 ORDER BY filter ASC";
        return $this->db->fetchAll($sql);
    }
    
    public function getFilterByUuid($uuid) {
        $sql = "SELECT * FROM blog_filter WHERE uuid = ?";
        return $this->db->fetchOne($sql, [$uuid]);
    }
}