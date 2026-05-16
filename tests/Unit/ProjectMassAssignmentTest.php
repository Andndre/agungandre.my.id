<?php

namespace Tests\Unit;

use App\Models\Project;
use PHPUnit\Framework\TestCase;

class ProjectMassAssignmentTest extends TestCase
{
    public function test_project_fillable_attributes_include_title(): void
    {
        $project = new Project;

        $fillable = $project->getFillable();

        $this->assertContains('title', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('slug', $fillable);
    }

    public function test_project_can_be_instantiated_with_mass_assignment(): void
    {
        $project = new Project([
            'title' => 'Test Title',
            'description' => 'Test Description',
            'slug' => 'test-slug',
        ]);

        $this->assertEquals('Test Title', $project->title);
        $this->assertEquals('Test Description', $project->description);
        $this->assertEquals('test-slug', $project->slug);
    }
}
