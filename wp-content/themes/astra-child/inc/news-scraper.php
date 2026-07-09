<?php

class News_Scraper {
    private $site_url = 'https://autopro.com.vn/';
    private $cache_key = 'autopro_full_data';
    private $cache_time = HOUR_IN_SECONDS;

    public function __construct() {
        add_shortcode('advanced_car_news', [$this, 'render_shortcode']);
        add_action('admin_post_refresh_autopro', [$this, 'manual_refresh']);
        add_action('wp_ajax_refresh_autopro', [$this, 'ajax_refresh']);
        add_action('wp_ajax_nopriv_refresh_autopro', [$this, 'ajax_refresh']);
    }

    public function fetch_all() {
        $cached = get_transient($this->cache_key);
        if (false !== $cached) {
            return $cached;
        }

        $data = $this->build_full_dataset();
        if (!empty($data)) {
            set_transient($this->cache_key, $data, $this->cache_time);
        }

        return $data;
    }

    private function build_full_dataset() {
        $html = $this->fetch_html($this->site_url);
        if (!$html) {
            return [];
        }

        $categories = $this->extract_categories($html);
        if (empty($categories)) {
            return [];
        }

        $articles = [];
        foreach ($categories as $slug => $name) {
            $category_html = $this->fetch_html($this->site_url . $slug . '.chn');
            if ($category_html) {
                $articles[$slug] = $this->extract_articles($category_html, $slug, $name);
            } else {
                $articles[$slug] = [];
            }
            usleep(200000);
        }

        return [
            'categories' => $categories,
            'articles'   => $articles,
            'last_updated' => current_time('mysql'),
        ];
    }

    private function fetch_html($url) {
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            error_log('News Scraper: ' . ($response->get_error_message() ?? 'HTTP ' . wp_remote_retrieve_response_code($response)));
            return false;
        }

        return wp_remote_retrieve_body($response);
    }

    private function extract_categories($html) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $categories = [];

        $nodes = $xpath->query("//nav[contains(@class, 'bottom')]//a[contains(@href, '.chn')]");

        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            $name = trim($node->nodeValue);
            if (empty($name)) {
                continue;
            }

            preg_match('/\/([^\/]+)\.chn/', $href, $matches);
            if (!empty($matches[1])) {
                $slug = $matches[1];
                if (!in_array($slug, ['home', 'video', 'lien-he', 'gioi-thieu'])) {
                    $categories[$slug] = $name;
                }
            }
        }

        return array_unique($categories);
    }

    private function extract_articles($html, $category_slug, $category_name) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $articles = [];

        $nodes = $xpath->query("//h3/a[contains(@href, '.chn')]");

        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            $title = trim($node->nodeValue);

            if (empty($title)) {
                continue;
            }

            if (strpos($href, 'http') !== 0) {
                $href = 'https://autopro.com.vn' . $href;
            }

            $time = '';
            $image = '';

            $article = $node->parentNode;
            while ($article && $article->nodeName !== 'article') {
                $article = $article->parentNode;
            }

            if ($article) {
                $time_nodes = $xpath->query(".//span[contains(@class, 'timeago')]", $article);
                if ($time_nodes->length > 0) {
                    $time = trim($time_nodes->item(0)->getAttribute('title'));
                }
                if (empty($time)) {
                    $time_nodes = $xpath->query(".//span[contains(text(), 'giờ') or contains(text(), 'phút')]", $article);
                    if ($time_nodes->length > 0) {
                        $time = trim($time_nodes->item(0)->nodeValue);
                    }
                }

                $img_nodes = $xpath->query(".//img", $article);
                if ($img_nodes->length > 0) {
                    $image = $img_nodes->item(0)->getAttribute('src');
                    if (strpos($image, 'http') !== 0) {
                        $image = 'https://autopro.com.vn' . $image;
                    }
                }
            }

            $articles[] = [
                'title'         => $title,
                'link'          => $href,
                'time'          => $time,
                'image'         => $image,
                'category_slug' => $category_slug,
                'category_name' => $category_name,
                'source'        => 'AutoPro'
            ];
        }

        $unique = [];
        foreach ($articles as $item) {
            $unique[$item['link']] = $item;
        }
        $articles = array_values($unique);

        return array_slice($articles, 0, 20);
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'per_page' => 10,
        ], $atts);

        $data = $this->fetch_all();

        if (empty($data) || empty($data['articles'])) {
            return '<p>' . __t('scraper_no_news', 'No car news available.') . '</p>';
        }

        $categories = $data['categories'];
        $articles_by_category = $data['articles'];

        $all_articles = [];
        foreach ($articles_by_category as $category_articles) {
            $all_articles = array_merge($all_articles, $category_articles);
        }
        usort($all_articles, function($a, $b) {
            return strcmp($a['time'] ?? '', $b['time'] ?? '');
        });

        $per_page = intval($atts['per_page']);
        $total_articles = count($all_articles);

        ob_start();
        ?>
        <div class="advanced-scraped-news">
            <div class="news-controls">
                <div class="category-filters">
                    <a href="#" data-category="all" class="active">
                        <?php _te('scraped_all_news', 'All News'); ?>
                    </a>
                    <?php foreach ($categories as $slug => $name) : ?>
                        <a href="#" data-category="<?php echo esc_attr($slug); ?>">
                            <?php echo esc_html($name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="news-count">
                    <?php _te('scraped_total_articles', 'Total'); ?>: <span id="total-count"><?php echo $total_articles; ?></span>
                </div>
            </div>

            <div class="news-grid" id="news-grid">
                <?php foreach ($all_articles as $item) : ?>
                    <div class="news-card" data-category="<?php echo esc_attr($item['category_slug']); ?>" style="display: none;">
                        <?php if ($item['image']) : ?>
                            <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>" loading="lazy" />
                        <?php endif; ?>
                        <div class="news-card-content">
                            <a href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener">
                                <h3><?php echo esc_html($item['title']); ?></h3>
                            </a>
                            <div class="news-meta">
                                <?php if ($item['time']) : ?>
                                    <span class="news-time"><?php echo esc_html($item['time']); ?></span>
                                <?php endif; ?>
                                <span class="news-category"><?php echo esc_html($item['category_name']); ?></span>
                            </div>
                            <p class="news-source"><?php _te('scraper_source', 'Source'); ?>: <?php echo esc_html($item['source']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="news-pagination" id="news-pagination"></div>

            <p class="news-disclaimer">
                <?php _te('scraped_disclaimer', 'News articles are sourced from AutoPro and are for informational purposes only.'); ?>
            </p>
        </div>

        <script>
            (function() {
                const allCards = document.querySelectorAll('#news-grid .news-card');
                const totalCountSpan = document.getElementById('total-count');
                const paginationContainer = document.getElementById('news-pagination');
                const filters = document.querySelectorAll('.category-filters a');
                const searchInput = document.getElementById('scraped-news-search');

                let currentCategory = 'all';
                let currentPage = 1;
                let currentSearch = '';
                const perPage = <?php echo $per_page; ?>;

                function getFilteredCards(category, search) {
                    const cards = [];
                    const searchLower = search.toLowerCase().trim();
                    allCards.forEach(card => {
                        const cardCategory = card.dataset.category || '';
                        const titleEl = card.querySelector('.news-card-content h3');
                        const title = titleEl ? titleEl.textContent.toLowerCase() : '';
                        const matchesCategory = (category === 'all' || cardCategory === category);
                        const matchesSearch = !searchLower || title.indexOf(searchLower) !== -1;
                        if (matchesCategory && matchesSearch) {
                            cards.push(card);
                        }
                    });
                    return cards;
                }

                function renderPage(category, page, search) {
                    const filtered = getFilteredCards(category, search);
                    const total = filtered.length;
                    const totalPages = Math.ceil(total / perPage) || 1;

                    if (totalCountSpan) {
                        totalCountSpan.textContent = total;
                    }

                    allCards.forEach(card => card.style.display = 'none');

                    const start = (page - 1) * perPage;
                    const end = Math.min(start + perPage, total);
                    for (let i = start; i < end; i++) {
                        filtered[i].style.display = 'block';
                    }

                    renderPagination(totalPages, page, category, search);
                }

                function renderPagination(totalPages, current, category, search) {
                    if (totalPages <= 1) {
                        paginationContainer.innerHTML = '';
                        return;
                    }

                    let html = '';
                    if (current > 1) {
                        html += '<a href="#" data-page="' + (current - 1) + '" class="page-numbers">' + '<?php _te("pagination_prev", "Previous"); ?>' + '</a>';
                    }

                    for (let i = 1; i <= totalPages; i++) {
                        const active = i === current ? 'current' : '';
                        html += '<a href="#" data-page="' + i + '" class="page-numbers ' + active + '">' + i + '</a>';
                    }

                    if (current < totalPages) {
                        html += '<a href="#" data-page="' + (current + 1) + '" class="page-numbers">' + '<?php _te("pagination_next", "Next"); ?>' + '</a>';
                    }

                    paginationContainer.innerHTML = html;

                    paginationContainer.querySelectorAll('a[data-page]').forEach(link => {
                        link.addEventListener('click', function(e) {
                            e.preventDefault();
                            const page = parseInt(this.dataset.page);
                            if (!isNaN(page)) {
                                renderPage(category, page, search);
                                document.getElementById('news-grid').scrollIntoView({ behavior: 'smooth' });
                            }
                        });
                    });
                }

                filters.forEach(filter => {
                    filter.addEventListener('click', function(e) {
                        e.preventDefault();
                        const category = this.dataset.category || 'all';

                        filters.forEach(f => f.classList.remove('active'));
                        this.classList.add('active');

                        currentCategory = category;
                        currentPage = 1;
                        renderPage(category, currentPage, currentSearch);
                    });
                });

                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        currentSearch = this.value;
                        currentPage = 1;
                        renderPage(currentCategory, currentPage, currentSearch);
                    });
                }

                renderPage('all', 1, '');
            })();
        </script>
        <?php
        return ob_get_clean();
    }

    public function manual_refresh() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized.');
        }

        delete_transient($this->cache_key);
        $this->fetch_all();

        wp_redirect(add_query_arg('refreshed', '1', wp_get_referer()));
        exit;
    }

    public function ajax_refresh() {
        check_ajax_referer('refresh_autopro', 'nonce');

        delete_transient($this->cache_key);
        $data = $this->fetch_all();

        wp_send_json([
            'success' => !empty($data),
            'message' => !empty($data) ? 'Refreshed successfully.' : 'Failed to refresh.'
        ]);
    }
}

new News_Scraper();