<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lead;
use App\Models\User;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin ? $admin->id : null;

        $studentLeads = [
            [
                'name' => 'Aditya Sharma',
                'email' => 'aditya.sharma@example.com',
                'phone' => '9827364510',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'lead_type' => 'student',
                'source' => 'facebook',
                'school_name' => 'St. Xavier High School',
                'degree_college' => 'IIT Bombay',
                'degree_name' => 'B.Tech',
                'degree_specialization' => 'Computer Science',
                'status' => 'new',
            ],
            [
                'name' => 'Priya Patel',
                'email' => 'priya.patel@example.com',
                'phone' => '9123456780',
                'city' => 'Ahmedabad',
                'state' => 'Gujarat',
                'lead_type' => 'student',
                'source' => 'google',
                'school_name' => 'Global English School',
                'inter_college' => 'Gujarat College',
                'inter_stream' => 'Commerce',
                'status' => 'contacted',
            ],
            [
                'name' => 'Rahul Verma',
                'email' => 'rahul.verma@example.com',
                'phone' => '8877665544',
                'city' => 'Delhi',
                'state' => 'Delhi',
                'lead_type' => 'student',
                'source' => 'walk-in',
                'school_name' => 'Delhi Public School',
                'degree_college' => 'University of Delhi',
                'degree_name' => 'B.Sc',
                'degree_specialization' => 'Mathematics',
                'status' => 'interested',
            ],
            [
                'name' => 'Ananya Iyer',
                'email' => 'ananya.iyer@example.com',
                'phone' => '9988776655',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'lead_type' => 'student',
                'source' => 'instagram',
                'school_name' => 'Loyola Matriculation',
                'degree_college' => 'Anna University',
                'degree_name' => 'B.E',
                'degree_specialization' => 'Electronics',
                'status' => 'new',
            ],
            [
                'name' => 'Sandeep Singh',
                'email' => 'sandeep.singh@example.com',
                'phone' => '7766554433',
                'city' => 'Chandigarh',
                'state' => 'Punjab',
                'lead_type' => 'student',
                'source' => 'referred',
                'referred_by' => 'Rahul Verma',
                'school_name' => 'Chitkara School',
                'status' => 'new',
            ],
            [
                'name' => 'Megha Das',
                'email' => 'megha.das@example.com',
                'phone' => '9000112233',
                'city' => 'Kolkata',
                'state' => 'West Bengal',
                'lead_type' => 'student',
                'source' => 'facebook',
                'school_name' => 'South Point High School',
                'degree_college' => 'Jadavpur University',
                'degree_name' => 'BCA',
                'status' => 'interested',
            ],
            [
                'name' => 'Karthik Rao',
                'email' => 'karthik.rao@example.com',
                'phone' => '8123456000',
                'city' => 'Bangalore',
                'state' => 'Karnataka',
                'lead_type' => 'student',
                'source' => 'google',
                'degree_college' => 'RV College of Engineering',
                'status' => 'contacted',
            ],
            [
                'name' => 'Ishita Gupta',
                'email' => 'ishita.gupta@example.com',
                'phone' => '9888223344',
                'city' => 'Lucknow',
                'state' => 'Uttar Pradesh',
                'lead_type' => 'student',
                'source' => 'facebook',
                'school_name' => 'CMS Aliganj',
                'status' => 'new',
            ],
        ];

        $professionalLeads = [
            [
                'name' => 'Vikram Malhotra',
                'email' => 'vikram.m@techcorp.com',
                'phone' => '9876543210',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'lead_type' => 'professional',
                'source' => 'linkedin',
                'current_company' => 'TechCorp Solutions',
                'current_designation' => 'Software Engineer',
                'experience_years' => 3,
                'current_skills' => 'Java, Spring Boot, MySQL',
                'status' => 'interested',
            ],
            [
                'name' => 'Swati Reddy',
                'email' => 'swati.reddy@fintech.in',
                'phone' => '9123456123',
                'city' => 'Hyderabad',
                'state' => 'Telangana',
                'lead_type' => 'professional',
                'source' => 'google',
                'current_company' => 'FinTech Hub',
                'current_designation' => 'Data Analyst',
                'experience_years' => 2,
                'current_skills' => 'Python, SQL, Tableau',
                'status' => 'new',
            ],
            [
                'name' => 'Abhishek Nair',
                'email' => 'abhishek.n@startup.com',
                'phone' => '8899001122',
                'city' => 'Bangalore',
                'state' => 'Karnataka',
                'lead_type' => 'professional',
                'source' => 'referred',
                'current_company' => 'QuickStart AI',
                'current_designation' => 'Junior Developer',
                'experience_years' => 1,
                'current_skills' => 'JavaScript, React, Node.js',
                'status' => 'contacted',
            ],
            [
                'name' => 'Rohan Joshi',
                'email' => 'rohan.joshi@bank.com',
                'phone' => '7788990011',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'lead_type' => 'professional',
                'source' => 'direct',
                'current_company' => 'HDFC Bank',
                'current_designation' => 'System Admin',
                'experience_years' => 5,
                'current_skills' => 'Linux, Networking, Security',
                'status' => 'new',
            ],
            [
                'name' => 'Neha Kapoor',
                'email' => 'neha.k@marketingpros.com',
                'phone' => '9000119000',
                'city' => 'Gurgaon',
                'state' => 'Haryana',
                'lead_type' => 'professional',
                'source' => 'facebook',
                'current_company' => 'Marketing Pros',
                'current_designation' => 'Content Manager',
                'status' => 'interested',
            ],
        ];

        $courses = \App\Models\Course::pluck('id')->toArray();

        foreach (array_merge($studentLeads, $professionalLeads) as $leadData) {
            $leadData['created_by'] = $adminId;
            if (!empty($courses)) {
                $leadData['interested_course_id'] = $courses[array_rand($courses)];
            }
            Lead::create($leadData);
        }
    }
}
