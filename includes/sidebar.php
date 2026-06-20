<aside class="app-sidebar bg-body-secondary shadow">
    <div class="sidebar-brand">
        <a href="<?= BASE_URL ?>index.php" class="brand-link">
            <img src="<?= $settings['system_logo'] ?>" alt="Logo" class="brand-image opacity-75 shadow">
            <span class="brand-text fw-light"><?= $settings['system_name'] ?></span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                <?php
                // Get all active page IDs from breadcrumbs to keep parents open
                $activeIds = array_column($breadcrumbs ?? [], 'id');

                function buildMenu($pdo, $parentId = 0, $userRole, $currentUrl, $activeIds)
                {
                    $sql = "
                        SELECT p.* FROM sys_pages p
                        JOIN role_access ra ON p.id = ra.page_id
                        WHERE p.parent_id = ? AND ra.role_key = ? AND p.is_menu = 1
                        ORDER BY p.sort_order ASC
                    ";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$parentId, $userRole]);
                    $items = $stmt->fetchAll();

                    foreach ($items as $item) {
                        $childStmt = $pdo->prepare("SELECT COUNT(*) FROM sys_pages WHERE parent_id = ?");
                        $childStmt->execute([$item['id']]);
                        $hasChildren = $childStmt->fetchColumn() > 0;

                        // Check if this item or any of its children are currently active
                        $isCurrent = (strpos($currentUrl, $item['page_url']) !== false && $item['page_url'] !== '#');
                        $isAncestor = in_array($item['id'], $activeIds);

                        $menuOpen = ($isAncestor && $hasChildren) ? 'menu-open' : '';
                        $activeClass = ($isCurrent || ($isAncestor && $item['page_url'] == '#')) ? 'active' : '';

                        echo '<li class="nav-item ' . $menuOpen . '">';
                        echo '<a href="' . ($hasChildren ? '#' : BASE_URL . $item['page_url']) . '" class="nav-link ' . $activeClass . '">';
                        echo '<i class="nav-icon ' . $item['icon_class'] . '"></i>';
                        echo '<p>' . htmlspecialchars($item['page_name']);
                        if ($hasChildren) {
                            echo '<i class="nav-arrow bi bi-chevron-right"></i>';
                        }
                        echo '</p></a>';

                        if ($hasChildren) {
                            echo '<ul class="nav nav-treeview">';
                            buildMenu($pdo, $item['id'], $userRole, $currentUrl, $activeIds);
                            echo '</ul>';
                        }
                        echo '</li>';
                    }
                }

                $cur = substr($_SERVER['SCRIPT_NAME'], strlen('/complaint/'));
                buildMenu($pdo, 0, $_SESSION['role'], $cur, $activeIds);
                ?>

            </ul>
        </nav>
    </div>
</aside>