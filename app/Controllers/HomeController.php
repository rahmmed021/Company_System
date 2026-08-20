<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\BaseRepository;
use PDO;
use Throwable;

final class HomeController extends Controller
{
    public function index(): void
    {
        $fallbackSections = [
            'hero' => ['title_en' => 'Nousin Enterprise', 'title_bn' => 'নওসিন এন্টারপ্রাইজ', 'body_en' => 'Professional contracting, fabrication and workforce management services.', 'body_bn' => 'পেশাদার কন্ট্রাক্টিং, ফ্যাব্রিকেশন ও কর্মী ব্যবস্থাপনা সেবা।', 'image_path' => 'assets/images/nousin-logo.svg'],
            'about' => ['title_en' => 'About Nousin Enterprise', 'title_bn' => 'নওসিন এন্টারপ্রাইজ সম্পর্কে', 'body_en' => 'A company-focused ERP-supported contracting operation for projects, workers, expenses and equipment.', 'body_bn' => 'প্রকল্প, কর্মী, খরচ ও ইকুইপমেন্ট পরিচালনার জন্য ইআরপি-সমর্থিত কন্ট্রাক্টিং প্রতিষ্ঠান।', 'image_path' => 'assets/images/nousin-logo.svg'],
            'contact' => ['title_en' => 'Contact', 'title_bn' => 'যোগাযোগ', 'body_en' => 'Dhaka, Bangladesh | 01700000000 | admin@example.com', 'body_bn' => 'ঢাকা, বাংলাদেশ | 01700000000 | admin@example.com', 'image_path' => 'assets/images/nousin-logo.svg'],
        ];
        $sections = $fallbackSections;
        $updates = [];
        $services = [];
        $media = [];
        $recentProjects = [];
        $runningProjects = [];

        if ($this->databaseAvailable()) {
            try {
                foreach ((new BaseRepository('homepage_sections'))->all('is_active = 1', [], 'sort_order ASC') as $row) {
                    $sections[$row['section_key']] = $row;
                }
                $updates = (new BaseRepository('homepage_updates'))->all('is_active = 1', [], 'published_at DESC, id DESC LIMIT 6');
                $services = (new BaseRepository('homepage_services'))->all('is_active = 1', [], 'sort_order ASC, id DESC');
                $media = (new BaseRepository('homepage_media'))->all('is_active = 1', [], 'sort_order ASC, id DESC');
                $recentProjects = (new BaseRepository('projects'))->all("status IN ('completed','running')", [], 'id DESC LIMIT 6');
                $runningProjects = (new BaseRepository('projects'))->all("status = 'running'", [], 'id DESC LIMIT 6');
            } catch (Throwable) {
                $services = [];
            }
        }

        if (!$services) {
            $services = [
                ['title_en' => 'Steel Fabrication', 'title_bn' => 'স্টিল ফ্যাব্রিকেশন', 'body_en' => 'Shed, grill, structure and welding works.', 'body_bn' => 'শেড, গ্রিল, স্ট্রাকচার ও ওয়েল্ডিং কাজ।', 'icon' => 'fa-screwdriver-wrench'],
                ['title_en' => 'Project Workforce', 'title_bn' => 'প্রকল্প কর্মী', 'body_en' => 'Foreman and labour team deployment.', 'body_bn' => 'ফোরম্যান ও শ্রমিক দল নিয়োগ।', 'icon' => 'fa-people-group'],
                ['title_en' => 'Equipment Support', 'title_bn' => 'ইকুইপমেন্ট সাপোর্ট', 'body_en' => 'Tools and equipment assignment for projects.', 'body_bn' => 'প্রকল্পের জন্য সরঞ্জাম ও ইকুইপমেন্ট বরাদ্দ।', 'icon' => 'fa-toolbox'],
            ];
        }

        $this->render('home/index', compact('sections', 'updates', 'services', 'media', 'recentProjects', 'runningProjects'), 'layouts/public');
    }

    private function databaseAvailable(): bool
    {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', env('DB_HOST', '127.0.0.1'), env('DB_PORT', '3306'), env('DB_DATABASE', 'contracting_erp'));
            new PDO($dsn, (string) env('DB_USERNAME', 'root'), (string) env('DB_PASSWORD', ''), [PDO::ATTR_TIMEOUT => 1]);
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
