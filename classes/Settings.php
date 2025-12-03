<?php
class Settings {
    private $db;
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    // Discount Settings
    public function getDiscountRuleApplied() {
        $sql = "SELECT discount_rule_applied FROM plans LIMIT 1";
        $result = $this->db->fetchOne($sql);
        return $result ? $result['discount_rule_applied'] : 0;
    }
    
    public function updateDiscountRuleApplied($rule) {
        $sql = "UPDATE plans SET discount_rule_applied = ?";
        try {
            $this->db->execute($sql, [$rule]);
            return ['success' => true, 'message' => 'Discount rule updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating discount rule: ' . $e->getMessage()];
        }
    }
    
    public function getGeneralDiscount() {
        $sql = "SELECT single_discount FROM plans LIMIT 1";
        $result = $this->db->fetchOne($sql);
        return $result ? $result['single_discount'] : null;
    }
    
    public function updateGeneralDiscount($discounts) {
        $discountJson = $this->formatDiscounts($discounts);
        $sql = "UPDATE plans SET single_discount = ?";
        
        try {
            $this->db->execute($sql, [$discountJson]);
            return ['success' => true, 'message' => 'General discount updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating general discount: ' . $e->getMessage()];
        }
    }
    
    private function formatDiscounts($discounts) {
        if (empty($discounts) || !is_array($discounts)) {
            return null;
        }
        
        $formattedDiscounts = [];
        foreach ($discounts as $discount) {
            if (!empty($discount['qtd']) && !empty($discount['percent'])) {
                $formattedDiscounts[] = [
                    'qtd' => (string)$discount['qtd'],
                    'percent' => (string)$discount['percent']
                ];
            }
        }
        
        return empty($formattedDiscounts) ? null : json_encode($formattedDiscounts);
    }
    
    // Blog Filter Settings
    public function createBlogFilter($filter) {
        $uuid = uniqid();
        $sql = "INSERT INTO blog_filter (uuid, filter, is_show) VALUES (?, ?, 1)";
        
        try {
            $this->db->execute($sql, [$uuid, $filter]);
            return ['success' => true, 'message' => 'Filter created successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating filter: ' . $e->getMessage()];
        }
    }
    
    public function updateFilterVisibility($uuid, $isShow) {
        $sql = "UPDATE blog_filter SET is_show = ? WHERE uuid = ?";
        
        try {
            $this->db->execute($sql, [$isShow, $uuid]);
            return true;
        } catch (Exception $e) {
            error_log("Error updating filter visibility: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateAllFilterVisibility($filters) {
        try {
            foreach ($filters as $uuid => $isShow) {
                $this->updateFilterVisibility($uuid, $isShow);
            }
            return ['success' => true, 'message' => 'Filter visibility updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating filters: ' . $e->getMessage()];
        }
    }
    
    // Display Settings
    public function getWebsiteConfig() {
        $sql = "SELECT * FROM website_conf WHERE id = 1";
        $result = $this->db->fetchOne($sql);
        
        if (!$result) {
            // Create default config if doesn't exist
            $this->db->execute("INSERT INTO website_conf (id, home, faq, contact) VALUES (1, NULL, NULL, NULL)");
            return ['id' => 1, 'home' => null, 'faq' => null, 'contact' => null];
        }
        
        return $result;
    }
    
    public function updateWebsiteConfig($section, $posts) {
        $postsJson = json_encode([
            'post1' => $posts[0] ?? '',
            'post2' => $posts[1] ?? '',
            'post3' => $posts[2] ?? '',
            'post4' => $posts[3] ?? '',
            'post5' => $posts[4] ?? '',
            'post6' => $posts[5] ?? ''
        ]);
        
        $sql = "UPDATE website_conf SET $section = ? WHERE id = 1";
        
        try {
            $this->db->execute($sql, [$postsJson]);
            return ['success' => true, 'message' => ucfirst($section) . ' settings updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating ' . $section . ' settings: ' . $e->getMessage()];
        }
    }
}