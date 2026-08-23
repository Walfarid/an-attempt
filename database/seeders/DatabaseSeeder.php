<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Post;
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
        $skills = $this->seedSkills();
        $this->seedExperience();
        $this->seedProjects($skills);
        $this->seedEducation();
        $this->seedPublication();

        if (Post::count() === 0) {
            Post::factory()->count(3)->create();
            Post::factory()->draft()->count(1)->create();
        }

        if (ContactMessage::count() === 0) {
            ContactMessage::factory()->count(2)->create();
            ContactMessage::factory()->read()->count(1)->create();
        }
    }

    private function seedProfile(): void
    {
        Profile::updateOrCreate(['id' => 1], [
            'name' => 'Walfarid Hermawan Limbong',
            'headline' => 'Software Engineer',
            'bio' => <<<'MD'
                Software developer with over 6 years of experience in **application development, API management, and deployment platforms** — building platforms and backend services from the ground up: REST APIs, third-party integrations, background processes, and reusable components such as a custom CMS.

                Manages deployment and Linux environments from development to production with container platforms (Docker, Kubernetes, OpenShift) and automation (Ansible, Terraform). Wrote security and compliance documentation for a financial institution based on NIST CSF, OWASP, PCI DSS, and CIS Benchmarks.
                MD,
            'location' => 'Singapore · UTC+8',
            'github_url' => 'https://github.com/Walfarid/an-attempt',
            'linkedin_url' => null,
            'avatar_path' => null,
        ]);
    }

    /**
     * @return array<string, int> skill name → id, for project syncing
     */
    private function seedSkills(): array
    {
        $skills = [
            // Programming languages
            ['name' => 'Go', 'category' => 'languages'],
            ['name' => 'Java', 'category' => 'languages'],
            ['name' => 'PHP', 'category' => 'languages'],
            ['name' => 'Python', 'category' => 'languages'],
            ['name' => 'SQL', 'category' => 'languages'],
            ['name' => 'JavaScript', 'category' => 'languages'],
            ['name' => 'Kotlin', 'category' => 'languages'],
            ['name' => 'Swift', 'category' => 'languages'],

            // Frameworks
            ['name' => 'Laravel', 'category' => 'frameworks'],
            ['name' => 'Slim Framework', 'category' => 'frameworks'],
            ['name' => 'Nuxt.js (Vue)', 'category' => 'frameworks'],
            ['name' => 'Inertia.js', 'category' => 'frameworks'],
            ['name' => 'Tailwind CSS', 'category' => 'frameworks'],

            // Databases
            ['name' => 'PostgreSQL', 'category' => 'databases'],
            ['name' => 'MySQL', 'category' => 'databases'],
            ['name' => 'MariaDB', 'category' => 'databases'],
            ['name' => 'MongoDB', 'category' => 'databases'],
            ['name' => 'Redis', 'category' => 'databases'],

            // DevOps & automation
            ['name' => 'Docker', 'category' => 'devops'],
            ['name' => 'Kubernetes', 'category' => 'devops'],
            ['name' => 'OpenShift', 'category' => 'devops'],
            ['name' => 'Ansible', 'category' => 'devops'],
            ['name' => 'Terraform', 'category' => 'devops'],
            ['name' => 'Git', 'category' => 'devops'],
            ['name' => 'CI/CD & Deployment Automation', 'category' => 'devops'],
            ['name' => 'Linux Server Administration', 'category' => 'devops'],
            ['name' => 'Nginx / Apache', 'category' => 'devops'],
            ['name' => 'SSL Configuration', 'category' => 'devops'],

            // Platform & architecture
            ['name' => 'Software Architecture', 'category' => 'platform'],
            ['name' => 'REST API Design & Management', 'category' => 'platform'],
            ['name' => 'Third-party API Integration', 'category' => 'platform'],
            ['name' => 'Custom CMS / WYSIWYG Editor', 'category' => 'platform'],
            ['name' => 'Background Job Processing', 'category' => 'platform'],
            ['name' => 'Real-time Messaging', 'category' => 'platform'],

            // Compliance & IT security
            ['name' => 'NIST CSF', 'category' => 'security'],
            ['name' => 'OWASP', 'category' => 'security'],
            ['name' => 'PCI DSS', 'category' => 'security'],
            ['name' => 'CIS Benchmarks', 'category' => 'security'],
            ['name' => 'Single Sign-On (SSO)', 'category' => 'security'],
            ['name' => 'Field-level DB Encryption (Acra)', 'category' => 'security'],
        ];

        $ids = [];

        foreach ($skills as $index => $skill) {
            $model = Skill::firstOrCreate(
                ['name' => $skill['name'], 'category' => $skill['category']],
            );
            $ids[$skill['name']] = $model->id;
            unset($index);
        }

        return $ids;
    }

    private function seedExperience(): void
    {
        Experience::updateOrCreate([
            'company' => 'PT Awan Teknologi Inovasi',
            'role' => 'Software Developer',
        ], [
            'location' => 'Jakarta Utara, DKI Jakarta',
            'started_at' => '2019-02-01',
            'ended_at' => '2025-07-31',
            'summary' => 'Managed application development from design through deployment and maintenance — HR, visitor management, product catalogue, and security systems.',
            'highlights' => [
                'Designed and built REST APIs in Go, PHP/Laravel, and Slim as the core backend for web and mobile clients, including third-party data integration',
                'Led development of a RESTful API for an HR system using Slim, including Single Sign-On via Google and Microsoft identity platforms',
                'Built reusable components as a custom CMS with a WYSIWYG editor, enabling non-technical teams to manage website content independently',
                'Ran Linux deployments on Nginx/Apache using Docker, Kubernetes, and OpenShift, automated with Ansible and Terraform',
                'Handled production support and post-release issues, including troubleshooting and performance work on a legacy PHP application',
                'Wrote security documentation for a financial institution aligned with NIST CSF, OWASP, PCI DSS, and CIS Benchmarks',
                'Built an integration platform for a security system: Go services processing NVR data, a custom mail server for device data, and REST APIs with background synchronization',
                'Integrated StellarCyber SIEM, Socket.io, and Firebase Cloud Messaging; automated threat identification via FortiGate, AlienVault, and VirusTotal APIs',
            ],
        ]);
    }

    /**
     * @param  array<string, int>  $skills
     */
    private function seedProjects(array $skills): void
    {
        $projects = [
            [
                'slug' => 'hr-platform-rest-api-sso',
                'title' => 'HR Platform · REST API & SSO',
                'description' => 'RESTful API backbone for an HR system built on Slim, with Single Sign-On wired into Google and Microsoft identity platforms.',
                'year' => 2021,
                'featured' => true,
                'sort_order' => 1,
                'skills' => ['Slim Framework', 'PHP', 'Single Sign-On (SSO)', 'REST API Design & Management'],
            ],
            [
                'slug' => 'security-integration-platform',
                'title' => 'Security Integration Platform',
                'description' => 'Go services ingesting Network Video Recorder data, a custom mail server for device traffic, and REST APIs with background processes keeping everything in sync.',
                'year' => 2023,
                'featured' => true,
                'sort_order' => 2,
                'skills' => ['Go', 'Third-party API Integration', 'Background Job Processing', 'Real-time Messaging'],
            ],
            [
                'slug' => 'custom-cms-wysiwyg-editor',
                'title' => 'Custom CMS · WYSIWYG Editor',
                'description' => 'A reusable content platform that lets non-technical teams manage website content entirely on their own.',
                'year' => 2020,
                'featured' => false,
                'sort_order' => 3,
                'skills' => ['Laravel', 'Custom CMS / WYSIWYG Editor', 'MySQL'],
            ],
            [
                'slug' => 'threat-identification-automation',
                'title' => 'Threat Identification Automation',
                'description' => 'Python automation correlating FortiGate, AlienVault, and VirusTotal APIs to surface and triage security threats.',
                'year' => 2024,
                'featured' => false,
                'sort_order' => 4,
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
                'published_at' => now(),
            ]);

            $model->skills()->sync(collect($project['skills'])->map(fn (string $name) => $skills[$name]));
        }
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
                'Designing Modern Software Systems',
                'Architecting Scalable Systems',
                'Securing Ubiquitous Systems',
                'Architecting AI Systems',
            ],
            'sort_order' => 1,
        ]);

        Education::updateOrCreate([
            'school' => 'Universitas Klabat',
            'degree' => 'Sarjana Komputer — Bachelor of Computer Science',
        ], [
            'started_at' => null,
            'ended_at' => '2018-07-31',
            'details' => [
                'GPA 3.5 / 4.0',
                'Thesis: Indoor Air Quality Monitoring System Based on Microcontroller, Android and IoT',
            ],
            'sort_order' => 2,
        ]);
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
