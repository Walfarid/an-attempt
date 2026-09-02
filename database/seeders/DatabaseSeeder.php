<?php

namespace Database\Seeders;

use App\Models\Education;
use App\Models\Experience;
use App\Models\Guide;
use App\Models\Post;
use App\Models\PrivacyPolicy;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Publication;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with Walfa's CV content.
     *
     * Idempotent: natural keys are matched first so re-running only
     * refreshes values instead of duplicating rows. Placeholder posts
     * and contact messages are only created while their tables are
     * empty.
     */
    public function run(): void
    {
        User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            // users.workos_id and users.avatar are NOT NULL; seed accounts
            // are not WorkOS-backed, mirroring the UserFactory placeholders.
            'workos_id' => 'fake-'.Str::random(10),
            'avatar' => '',
        ]);

        $this->seedProfile();
        $this->seedPrivacyPolicy();
        $skills = $this->seedSkills();
        $this->seedExperience();
        $this->seedProjects($skills);
        $this->seedEducation();
        $this->seedPublication();

        // Placeholder content only outside production: a live site should not
        // be seeded with fake posts/guides (and the factory requires the faker
        // dev dependency, which the production image does not ship).
        if (app()->environment('local', 'staging')) {
            if (Post::count() === 0) {
                Post::factory()->count(3)->create();
                Post::factory()->draft()->count(1)->create();
            }

            if (Guide::count() === 0) {
                Guide::factory()->count(2)->create();
                Guide::factory()->draft()->count(1)->create();
            }
        }
    }

    private function seedProfile(): void
    {
        Profile::updateOrCreate(['id' => 1], [
            'name' => 'Walfarid Hermawan Limbong',
            'headline' => 'Software Engineer',
            'bio' => <<<'MD'
                Builds software that works — tinkers with new technology, learns fast, and adapts to constraints. Primary languages are **PHP, Go, and Kotlin**, with working experience in C#, Swift, Java, JavaScript, and Python. Has built AI systems that convert natural language into structured output, and is pursuing an **MTech in Software Engineering at NUS** to strengthen backend/platform engineering and AI systems work — building on experience with event-driven microservices, real-time systems, AI pipelines, and security work.
                MD,
            'location' => 'Singapore · UTC+8',
            'github_url' => 'https://github.com/Walfarid/',
            'linkedin_url' => 'https://www.linkedin.com/in/walfarid-hermawan-limbong-272804376/',
            'avatar_path' => null,
        ]);
    }

    private function seedPrivacyPolicy(): void
    {
        PrivacyPolicy::updateOrCreate(['id' => 1], [
            'body' => <<<'MD'
                # Privacy disclosure

                This site is a personal portfolio run by an individual — no data brokers, no selling of data. Everything this site knows about you is described below.

                ## What the site owner collects

                - **Contact emails** — when you email the site owner through the "Send email" link, your email client handles delivery directly. No data is stored server-side.
                - **First-party analytics** — the site keeps its own lightweight counters of page views and outbound-link clicks (page path, referrer, user-agent, coarse device type, approximate country from IP, and a non-reversible hash of the IP used only to count unique visitors). These are aggregate numbers, not personal profiles.

                ## Third-party analytics

                These load **only after you accept** the consent banner. Nothing from either vendor runs before that choice.

                ### Microsoft Clarity

                Microsoft Clarity provides heatmaps, session recordings, and click tracking (clicks, scrolls, mouse movement, and basic device and browser information). It sets its own cookies on this site — including `_clck` (unique visitor ID, 1 year), `_clsk` (groups page views into one session recording, 1 day), and `MR`, `MUID`, `SM`, `ANONCHK`, and `CLID` on Microsoft domains. Clarity states it does not collect personally identifiable information and applies IP masking.

                - Privacy statement: [https://privacy.microsoft.com/privacystatement](https://privacy.microsoft.com/privacystatement)
                - Clarity privacy FAQ: [https://clarity.microsoft.com/privacy](https://clarity.microsoft.com/privacy)
                - Opt out of Clarity tracking: [https://clarity.microsoft.com/opt-out](https://clarity.microsoft.com/opt-out)

                ### Google Analytics 4

                Google Analytics 4 records pageviews and events (pages visited, time on site, referrer, approximate location, device and browser information). It sets Google cookies such as `_ga` (2 years) and `_ga_*` (2 years) to distinguish visitors and persist session state.

                - How Google uses data: [https://policies.google.com/technologies/partner-sites](https://policies.google.com/technologies/partner-sites)
                - Google privacy policy: [https://policies.google.com/privacy](https://policies.google.com/privacy)
                - Browser add-on to opt out of Google Analytics: [https://tools.google.com/dlpage/gaoptout/](https://tools.google.com/dlpage/gaoptout/)

                ## Cookies set by this site

                - **`laravel-session`** (2 hours) — signed session cookie that keeps you logged in to the dashboard. Marked HTTP-only, so scripts cannot read it.
                - **`XSRF-TOKEN`** (2 hours) — CSRF protection token for form submissions.
                - **`consent`** (1 year) — remembers your analytics consent decision (`accepted` or `declined`) so the banner is not shown again.
                - **`appearance`** (1 year) — remembers your light/dark theme choice.
                - **`sidebar_state`** (1 year) — remembers whether the dashboard sidebar is open.

                ## How consent works

                On your first visit a small bar at the bottom of the page asks whether analytics may load. **Decline** (or simply ignoring it) means no Clarity or Google Analytics code is ever fetched or run. **Accept** stores the `consent` cookie and loads both tools on subsequent page loads. The choice is stored in a first-party cookie, never on a server, and can be changed at any time with the **Cookie settings** button on this page — clearing the stored choice brings the bar back.

                ## Your choices

                Because the analytics tools only run with your consent, declining is the simplest opt-out and can be reversed in either direction at any time.
                MD,
        ]);
    }

    /**
     * @return array<string, int> skill name → id, for project syncing
     */
    private function seedSkills(): array
    {
        // Curated from the resume's Languages / Containers & Orchestration /
        // CI/CD & Automation sections, plus the frameworks and platforms the
        // projects below reference. Categories come from the SkillCategory
        // enum (languages/frameworks/databases/devops/platform/security).
        $skills = [
            // Resume "Languages"
            ['name' => 'PHP', 'category' => 'languages'],
            ['name' => 'Go', 'category' => 'languages'],
            ['name' => 'Kotlin', 'category' => 'languages'],
            ['name' => 'C#', 'category' => 'languages'],
            ['name' => 'Swift', 'category' => 'languages'],
            ['name' => 'Java', 'category' => 'languages'],
            ['name' => 'JavaScript', 'category' => 'languages'],
            ['name' => 'Python', 'category' => 'languages'],

            // Frameworks used by the projects below
            ['name' => 'Laravel', 'category' => 'frameworks'],
            ['name' => 'Slim Framework', 'category' => 'frameworks'],
            ['name' => 'Nuxt.js (Vue)', 'category' => 'frameworks'],
            ['name' => 'Inertia.js', 'category' => 'frameworks'],
            ['name' => 'Tailwind CSS', 'category' => 'frameworks'],
            ['name' => 'Bootstrap', 'category' => 'frameworks'],

            // Databases
            ['name' => 'MySQL', 'category' => 'databases'],
            ['name' => 'Redis', 'category' => 'databases'],

            // Resume "Containers & Orchestration" + "CI/CD & Automation"
            ['name' => 'Docker', 'category' => 'devops'],
            ['name' => 'Kubernetes', 'category' => 'devops'],
            ['name' => 'Red Hat OpenShift', 'category' => 'devops'],
            ['name' => 'ArgoCD', 'category' => 'devops'],
            ['name' => 'GitHub Actions', 'category' => 'devops'],
            ['name' => 'Terraform', 'category' => 'devops'],
            ['name' => 'CI/CD & Deployment Automation', 'category' => 'devops'],
            ['name' => 'Linux Server Administration', 'category' => 'devops'],
            ['name' => 'Nginx / Apache', 'category' => 'devops'],
            ['name' => 'SSL Configuration', 'category' => 'devops'],
            ['name' => 'Git', 'category' => 'devops'],
            ['name' => 'Unit Test Coverage Automation', 'category' => 'devops'],

            // Platform & architecture
            ['name' => 'Software Architecture', 'category' => 'platform'],
            ['name' => 'REST API Design & Management', 'category' => 'platform'],
            ['name' => 'Third-party API Integration', 'category' => 'platform'],
            ['name' => 'Custom CMS / WYSIWYG Editor', 'category' => 'platform'],
            ['name' => 'Background Job Processing', 'category' => 'platform'],
            ['name' => 'Real-time Messaging', 'category' => 'platform'],

            // Security: resume CI/CD gates + project security work
            ['name' => 'SCA', 'category' => 'security'],
            ['name' => 'SAST', 'category' => 'security'],
            ['name' => 'DAST', 'category' => 'security'],
            ['name' => 'OWASP', 'category' => 'security'],
            ['name' => 'PCI DSS', 'category' => 'security'],
            ['name' => 'CIS Benchmarks', 'category' => 'security'],
            ['name' => 'Field-level DB Encryption (Acra)', 'category' => 'security'],
        ];

        $ids = [];

        foreach ($skills as $skill) {
            $model = Skill::firstOrCreate(
                ['name' => $skill['name'], 'category' => $skill['category']],
            );
            $ids[$skill['name']] = $model->id;
        }

        // Re-seeding replaces the whole set: retire skills the resume no
        // longer lists (project_skill pivots cascade with the rows).
        Skill::whereNotIn('name', collect($skills)->pluck('name'))->delete();

        return $ids;
    }

    private function seedExperience(): void
    {
        // One entry per role at PT Awan Teknologi Inovasi; highlights are the
        // resume bullets verbatim.
        $roles = [
            [
                'role' => 'Senior Programmer',
                'started_at' => '2022-02-01',
                'ended_at' => '2025-06-30',
                'summary' => 'Backend and platform engineering for an Indonesian software house — Go and PHP APIs, Android/iOS applications, hardened deployments, and security documentation for a major bank.',
                'highlights' => [
                    'Legacy Code Refactoring — Company Internal App. Refactored a company internal application, reducing algorithm time complexity from O(n²) to O(n). Critical data processing paths now run in linear time instead of quadratic. Tech stack: PHP (vanilla).',
                    'HRM App — Android & iOS. Human Resource Management application used internally by a company, built for Android and iOS and consuming a REST API. Android version uses MVI architecture; integrates camera and location permissions for photo/attendance capture and location tracking. Tech stack: Kotlin, MVI architecture (Android), Swift (iOS), REST API.',
                    'Car Wash App — Android Prototype. Complete Android app prototype for a car wash service built from scratch in a few days, with all UI designed in XML layouts; also prototyped iOS app designs using Swift storyboards. Tech stack: Android (XML layouts), iOS prototyping (Swift storyboards).',
                    'Visitor Management System. Complete visitor management system with a Laravel backend, admin dashboard, and responsive frontend styled with Tailwind CSS. Tech stack: Laravel, Tailwind CSS.',
                    'Secure Web Portal — Laravel + Inertia.js. Secure web portal with field-level database encryption through an Acra server, and a Laravel dashboard on the StellarCyber SIEM API for the security team. Tech stack: Laravel, Inertia.js, Acra, StellarCyber SIEM API.',
                    'Bank Security Documents — Application & OpenShift Hardening. Co-authored security documents for one of the largest private banks in Indonesia — one covering application security, secure coding practices, OWASP, PCI DSS compliance and logging guidelines; the other covering Red Hat OpenShift hardening based on the CIS OpenShift Benchmark. Tech stack: Security documentation, Red Hat OpenShift, CIS Benchmark, OWASP, PCI DSS.',
                    'Site Access & Safety System — Face & Plate Recognition (Go). Built the backend integration and API in Go for a security system at Indonesia\'s largest state-owned oil company, connecting the company NVR (face recognition) and VMS (video recording), with an email-based detection pipeline, license plate recognition, and on-site alarms for unauthorized visitors. Contributed to reducing on-site safety incidents and deaths. Tech stack: Go, REST API, NVR face recognition, VMS, email integration, license plate recognition (LPR).',
                ],
            ],
            [
                'role' => 'Programmer',
                'started_at' => '2020-02-01',
                'ended_at' => '2022-01-31',
                'summary' => 'Mobile and web application development — Android/iOS apps, real-time chat backends, and bespoke company platforms.',
                'highlights' => [
                    'Company Catalogue — Android & iOS Apps. Mobile catalogue applications for a company, with Android (MVP architecture) and iOS versions, and a backend API built from scratch to serve both platforms, including a real-time chat support feature using Socket.IO. Tech stack: Java, Kotlin, MVP architecture (Android), Swift (iOS), PHP, Slim Framework, Socket.IO (Backend).',
                    'Music Notation Editor — Android App. Android application for creating, editing, and playing music notation, with MIDI playback and PDF export. Reverse-engineered an undocumented legacy library through decompiled Java source code. Tech stack: Java, Android SDK, MIDI, PDF generation.',
                    'Company Portfolio Website — Frontend from Scratch. Company portfolio website built with custom design and no templates. Tech stack: Nuxt.js, Tailwind CSS, Vue.',
                ],
            ],
            [
                'role' => 'Junior Programmer',
                'started_at' => '2019-02-01',
                'ended_at' => '2020-01-31',
                'summary' => 'Client website and CMS delivery plus automation — vanilla PHP MVC, cPanel deployments, and threat-intelligence integrations.',
                'highlights' => [
                    'PT. Hako Mandiri Perkasa — Company Profile Website. Company profile website for a cardboard/carton box manufacturer, reskinned from an existing HTML template with dynamic multi-language support (Indonesian/English) built in PHP and deployed on cPanel. Tech stack: PHP, Bootstrap 3, jQuery, AOS, Font Awesome, cPanel. URL: https://hakomp-box.com/',
                    'PT. Sysware Indonesia — Company Website. Company website with a custom CMS (vanilla PHP, MVC) for an IT solutions provider, managing news, events, and partner pages (25+ vendors). Tech stack: PHP (vanilla MVC), Bootstrap, jQuery, Font Awesome. URL: https://syswareindonesia.com/',
                    'Fortinet — AlienVault OTX Threat Intelligence Integration. Integrated Fortinet with AlienVault OTX to automate threat indicator lookups — sends indicators, checks threat intelligence feeds, and returns results to Fortinet. Tech stack: Python, Fortinet, AlienVault OTX API, REST API, threat intelligence.',
                ],
            ],
        ];

        foreach ($roles as $role) {
            Experience::updateOrCreate([
                'company' => 'PT Awan Teknologi Inovasi',
                'role' => $role['role'],
            ], [
                'location' => 'Jakarta Utara, DKI Jakarta',
                'started_at' => $role['started_at'],
                'ended_at' => $role['ended_at'],
                'summary' => $role['summary'],
                'highlights' => $role['highlights'],
            ]);
        }

        // Re-seeding replaces the roles: drop any entry from this employer
        // whose role is no longer in the resume.
        Experience::where('company', 'PT Awan Teknologi Inovasi')
            ->whereNotIn('role', ['Senior Programmer', 'Programmer', 'Junior Programmer'])
            ->delete();
    }

    /**
     * @param  array<string, int>  $skills
     */
    private function seedProjects(array $skills): void
    {
        // One project per distinguishable deliverable in the resume. Years
        // fall inside the role period each deliverable belongs to; featured
        // marks the four strongest.
        $projects = [
            [
                'slug' => 'hr-mobile-app',
                'title' => 'HRM App · Android & iOS',
                'description' => 'Human Resource Management application used internally by a company, built for Android and iOS and consuming a REST API — Android uses MVI architecture, with camera and location permissions for photo/attendance capture and location tracking.',
                'year' => 2023,
                'featured' => true,
                'sort_order' => 1,
                'skills' => ['Kotlin', 'Swift', 'REST API Design & Management'],
            ],
            [
                'slug' => 'site-access-safety-system',
                'title' => 'Site Access & Safety System · Face & Plate Recognition',
                'description' => 'Backend integration and API in Go for a security system at Indonesia\'s largest state-owned oil company — connects its NVR (face recognition) and VMS (video recording), runs an email-based detection pipeline, handles license plate recognition, and raises on-site alarms for visitors who should not be at the site.',
                'year' => 2024,
                'featured' => true,
                'sort_order' => 2,
                'skills' => ['Go', 'Third-party API Integration', 'Background Job Processing'],
            ],
            [
                'slug' => 'secure-web-portal-acra',
                'title' => 'Secure Web Portal · Laravel + Inertia.js',
                'description' => 'Secure web portal with field-level database encryption through an Acra server; a Laravel dashboard on the StellarCyber SIEM API provides the security team with a single view.',
                'year' => 2023,
                'featured' => true,
                'sort_order' => 3,
                'skills' => ['Laravel', 'Inertia.js', 'Field-level DB Encryption (Acra)'],
            ],
            [
                'slug' => 'bank-security-documents',
                'title' => 'Bank Security Documents · Application & OpenShift Hardening',
                'description' => 'Co-authored security documents for one of the largest private banks in Indonesia — application security, secure coding practices, OWASP and PCI DSS compliance, and Red Hat OpenShift hardening against the CIS Benchmark.',
                'year' => 2024,
                'featured' => true,
                'sort_order' => 4,
                'skills' => ['Red Hat OpenShift', 'OWASP', 'PCI DSS', 'CIS Benchmarks'],
            ],
            [
                'slug' => 'legacy-refactor-optimization',
                'title' => 'Legacy Refactoring · O(n²) → O(n)',
                'description' => 'Refactored a company internal application so critical data processing paths run in linear time instead of quadratic — algorithm time complexity dropped from O(n²) to O(n).',
                'year' => 2024,
                'featured' => false,
                'sort_order' => 5,
                'skills' => ['PHP'],
            ],
            [
                'slug' => 'visitor-management-system',
                'title' => 'Visitor Management System',
                'description' => 'Complete visitor management system — Laravel backend, admin dashboard, and a responsive frontend styled with Tailwind CSS.',
                'year' => 2022,
                'featured' => false,
                'sort_order' => 6,
                'skills' => ['Laravel', 'Tailwind CSS', 'MySQL'],
            ],
            [
                'slug' => 'car-wash-app-prototype',
                'title' => 'Car Wash App · Android Prototype',
                'description' => 'Complete Android app prototype for a car wash service built from scratch in a few days, with all UI designed in XML layouts — plus iOS app designs prototyped in Swift storyboards.',
                'year' => 2022,
                'featured' => false,
                'sort_order' => 7,
                'skills' => ['Kotlin', 'Java'],
            ],
            [
                'slug' => 'company-catalogue-mobile',
                'title' => 'Company Catalogue · Android & iOS',
                'description' => 'Mobile catalogue applications for a company — Android with MVP architecture and a backend API built from scratch to serve both platforms, including real-time chat support using Socket.IO.',
                'year' => 2020,
                'featured' => false,
                'sort_order' => 8,
                'skills' => ['Java', 'Kotlin', 'Swift', 'PHP', 'Slim Framework', 'Real-time Messaging'],
            ],
            [
                'slug' => 'music-notation-editor',
                'title' => 'Music Notation Editor · Android',
                'description' => 'Android application for creating, editing, and playing music notation with MIDI playback and PDF export — reverse-engineered an undocumented legacy library through decompiled Java source code.',
                'year' => 2020,
                'featured' => false,
                'sort_order' => 9,
                'skills' => ['Java'],
            ],
            [
                'slug' => 'company-portfolio-website',
                'title' => 'Company Portfolio Website',
                'description' => 'Company portfolio website built with custom design and no templates — from scratch with Nuxt.js and Tailwind CSS.',
                'year' => 2020,
                'featured' => false,
                'sort_order' => 10,
                'skills' => ['Nuxt.js (Vue)', 'Tailwind CSS', 'JavaScript'],
            ],
            [
                'slug' => 'hakomp-company-website',
                'title' => 'PT. Hako Mandiri Perkasa · Company Website',
                'description' => 'Company profile website for a cardboard/carton box manufacturer, reskinned from an existing template with dynamic Indonesian/English language support, deployed on cPanel.',
                'year' => 2019,
                'featured' => false,
                'sort_order' => 11,
                'live_url' => 'https://hakomp-box.com/',
                'skills' => ['PHP', 'Bootstrap', 'JavaScript'],
            ],
            [
                'slug' => 'sysware-cms',
                'title' => 'PT. Sysware Indonesia · Custom CMS',
                'description' => 'Company website for an IT solutions provider with a custom CMS built in vanilla PHP (MVC) — manages news, events, and partner pages for 25+ vendors.',
                'year' => 2019,
                'featured' => false,
                'sort_order' => 12,
                'live_url' => 'https://syswareindonesia.com/',
                'skills' => ['PHP', 'Bootstrap', 'Custom CMS / WYSIWYG Editor'],
            ],
            [
                'slug' => 'fortinet-threat-intel',
                'title' => 'Fortinet Threat Intelligence Integration',
                'description' => 'Integrated Fortinet with AlienVault OTX to automate threat indicator lookups — sends indicators, checks threat intelligence feeds, and returns results to Fortinet.',
                'year' => 2019,
                'featured' => false,
                'sort_order' => 13,
                'skills' => ['Python', 'Third-party API Integration'],
            ],
        ];

        foreach ($projects as $project) {
            $model = Project::updateOrCreate(['slug' => $project['slug']], [
                'title' => $project['title'],
                'description' => $project['description'],
                'year' => $project['year'],
                'featured' => $project['featured'],
                'sort_order' => $project['sort_order'],
                'live_url' => $project['live_url'] ?? null,
                'published_at' => now(),
            ]);

            $model->skills()->sync(collect($project['skills'])->map(fn (string $name) => $skills[$name]));
        }

        // Re-seeding replaces the project set: drop slugs the resume no
        // longer has (screenshots and pivots cascade with the rows).
        Project::whereNotIn('slug', collect($projects)->pluck('slug'))->delete();
    }

    private function seedEducation(): void
    {
        Education::updateOrCreate([
            'school' => 'National University of Singapore',
            'degree' => 'Master of Technology in Software Engineering',
        ], [
            'started_at' => '2025-08-01',
            'ended_at' => null,
            'details' => [
                'MTech, Software Engineering — Aug 2025 – Jul 2027 (expected)',
                'Coursework: Designing Modern Software Systems, Architecting Scalable Systems, Securing Ubiquitous Systems, Architecting AI Systems',
                'Projects:',
                'Chora — Bimodal Atomic Learning Ecosystem: AI-powered learning platform where AI agents orchestrate personalized learning.',
                'AssessorFlow — Question-Generator Agent: built the question-generator agent in a multi-agent quiz system — prompt engineering, RAG, output structuring, safety guardrails, and evaluation pipelines using Promptfoo and DeepEval; deployed to GCP via Terraform.',
                'GogoCars — Event-Driven Car-Rental Platform: event-driven car-rental microservices with direct LLM APIs for natural-language bookings; deployed to AWS via Terraform.',
                'ChronoFlow — Security Assessment: end-to-end security assessment — threat modeling, data flow mapping, risk evaluation, and mitigation design; deployed to GCP via Terraform.',
            ],
            'sort_order' => 1,
        ]);

        Education::updateOrCreate([
            'school' => 'Universitas Klabat',
            'degree' => 'S.Kom. (Cum Laude)',
        ], [
            'started_at' => '2013-08-01',
            'ended_at' => '2018-07-31',
            'details' => [
                'GPA 3.5 / 4.0',
            ],
            'sort_order' => 2,
        ]);

        // Re-seeding replaces the degrees: drop education rows whose degree
        // is no longer in the resume.
        Education::whereNotIn('degree', [
            'Master of Technology in Software Engineering',
            'S.Kom. (Cum Laude)',
        ])->delete();
    }

    private function seedPublication(): void
    {
        Publication::updateOrCreate(['doi_url' => 'https://doi.org/10.31154/cogito.v6i2.213.251-261'], [
            'citation' => 'Waworundeng, J., & Limbong, W. H. AirQMon: Indoor Air Quality Monitoring System Based on Microcontroller, Android and IoT.',
            'venue' => 'Cogito Smart Journal, 6(2), 251–261',
            'year' => 2020,
            'sort_order' => 1,
        ]);
    }
}
