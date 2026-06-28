<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplicationTrainingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CertificationController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommentReactionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyDashboardController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CvAnalysisController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\InterestController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\JobAlertController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobPostController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProfileCompletionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SalaryInsightController;
use App\Http\Controllers\SavedPostController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\SkillSuggestionAIController;
use App\Http\Controllers\SkillSuggestionController;
use App\Http\Controllers\StartupProjectController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\WithdrawApplicationController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//Route::middleware('audit')->group(function () {
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyRegisterOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/verify-otp', [AuthController::class, 'verifyLoginOtp']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->group(function () {


    Route::prefix('password')->group(function () {

        Route::post('/request-otp', [PasswordController::class, 'requestOtp']);
        Route::post('/verify-otp', [PasswordController::class, 'verifyOtp']);
        Route::post('/reset', [PasswordController::class, 'resetPassword']);
        Route::post('/resend-otp', [PasswordController::class, 'resendOtp']);

        Route::post('/change', [PasswordController::class, 'changePassword']);
    });
});


Route::middleware('auth:sanctum')->group(function () {

    Route::post('/account/delete/request', [AccountController::class, 'requestDeletion']);
    Route::post('/account/delete/confirm', [AccountController::class, 'confirmDeletion']);
    Route::post('/account/delete/restore', [AccountController::class, 'restore']);
});





    Route::middleware('auth:sanctum')->group(function () {


        Route::get('/skill-suggestions', [SkillSuggestionController::class, 'index']);
        Route::post('/skill-suggestions/{suggestion}/accept', [SkillSuggestionController::class, 'accept']);
        Route::post('/skill-suggestions/{suggestion}/reject', [SkillSuggestionController::class, 'reject']);
        Route::delete('/skill-suggestions/{suggestion}', [SkillSuggestionController::class, 'destroy']);


        Route::post('/ai/skill-suggestions', [SkillSuggestionAIController::class, 'suggest']);



        // Interests
        Route::get('/interests', [InterestController::class, 'index']);
        Route::post('/interests', [InterestController::class, 'store']);
        Route::post('/interests/{interest}', [InterestController::class, 'update']);
        Route::delete('/interests/{interest}', [InterestController::class, 'destroy']);

        // Trainings
        Route::get('/trainings', [TrainingController::class, 'index']);
        Route::post('/trainings', [TrainingController::class, 'store']);
        Route::post('/trainings/{training}', [TrainingController::class, 'update']);
        Route::delete('/trainings/{training}', [TrainingController::class, 'destroy']);

    });Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('cv')->group(function () {

        // البروفايل
        Route::get('/generate',    [CvAnalysisController::class, 'build']);

        // ملفات مرفوعة
        Route::get('/files',                      [CvAnalysisController::class, 'listFiles']);
        Route::get('/files/{id}',                 [CvAnalysisController::class, 'showFile']);
        Route::get('/files/{id}/download',        [CvAnalysisController::class, 'downloadOriginalFile']);
        // رفع واستخراج
        Route::post('/upload',         [CvAnalysisController::class, 'uploadAndExtract']);
        Route::post('/upload/confirm', [CvAnalysisController::class, 'confirmUploadedCv']);
        // التحليل
        Route::post('/analyze',    [CvAnalysisController::class, 'analyze']);
        // التحسين المباشر ← جديد
        Route::post('/enhance',                [CvAnalysisController::class, 'enhance']);
        Route::post('/enhance/save',           [CvAnalysisController::class, 'saveEnhanced']);

        Route::post('/pdf',        [CvAnalysisController::class, 'downloadPdf']);
        Route::post('/match',      [CvAnalysisController::class, 'match']);
    });
});

    Route::middleware(['auth:sanctum'])->group(function () {


        Route::post('/complaints', [ComplaintController::class, 'store']);
        Route::middleware(['admin'])->group(function () {
            Route::get('/complaints', [ComplaintController::class, 'index']);
            Route::post('/complaints/{id}/status', [ComplaintController::class, 'updateStatus']);
            Route::delete('/complaints/{id}', [ComplaintController::class, 'destroy']);
        });
    });
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/posts/{postId}/react', [ReactionController::class, 'react']);
        Route::delete('/posts/{postId}/react', [ReactionController::class, 'remove']);
        Route::get('/posts/{postId}/reactions', [ReactionController::class, 'list']);
        Route::get('/posts/{postId}/reactions/stats', [ReactionController::class, 'stats']);
        Route::get('/posts/{postId}/reactions/total', [ReactionController::class, 'total']);
    });


    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/search', [SearchController::class, 'search']);
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/company/me', [CompanyController::class, 'myCompany']);
    Route::post('/company/update', [CompanyController::class, 'update']);
    Route::post('/company/document', [CompanyController::class, 'uploadDocument']);
    Route::get('/company/documents', [CompanyController::class, 'documents']);
});
Route::middleware(['auth:sanctum', 'company.approved'])
    ->prefix('company/dashboard')
    ->group(function () {
        Route::get('/', [CompanyDashboardController::class, 'index']);
        Route::get('/jobs', [CompanyDashboardController::class, 'jobs']);
        Route::get('/applications', [CompanyDashboardController::class, 'applications']);
    });

Route::get('/companies/{id}/profile', [CompanyController::class, 'publicProfile']);


Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {


        Route::get('/dashboard', [AdminController::class, 'dashboard']);


        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/{id}', [AdminController::class, 'showUser']);
        Route::post('/users/{id}/ban', [AdminController::class, 'banUser']);
        Route::post('/users/{id}/unban', [AdminController::class, 'unbanUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);


        Route::get('/companies', [AdminController::class, 'companies']);
        Route::get('/companies/{id}', [AdminController::class, 'showCompany']);
        Route::post('/companies/{id}/approve', [AdminController::class, 'approveCompany']);
        Route::post('/companies/{id}/reject', [AdminController::class, 'rejectCompany']);


        Route::get('/jobs', [AdminController::class, 'jobs']);
        Route::get('/jobs/{id}', [AdminController::class, 'showJob']);
        Route::delete('/jobs/{id}', [AdminController::class, 'deleteJob']);

        // ✅ الموافقة/الرفض بين الشركة والمستخدم
        Route::get('/projects', [AdminController::class, 'projects']);
        Route::get('/projects/{id}', [AdminController::class, 'showProject']);
        Route::delete('/projects/{id}', [AdminController::class, 'deleteProject']); // body: { reason }


        Route::post('/categories', [CategoryController::class, 'store']);
        Route::post('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        Route::post('/categories/{id}/toggle', [CategoryController::class, 'toggle']);
    });


Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {

        Route::post('/saved-posts/toggle', [SavedPostController::class, 'toggle']);
        Route::get('/me/saved-posts', [SavedPostController::class, 'mySaved']);
        Route::get('/posts/{postId}/is-saved', [SavedPostController::class, 'isSaved']);
    });

Route::get('/jobs', [JobPostController::class, 'index']);
Route::get('/jobs/{id}', [JobPostController::class, 'show']);

    Route::middleware('auth:sanctum', 'company.approved')->group(function () {
        Route::post('/jobs', [JobPostController::class, 'store']);
        Route::post('/jobs/{id}', [JobPostController::class, 'update']);
        Route::delete('/jobs/{id}', [JobPostController::class, 'destroy']);
       Route::get('/jobs/{jobId}/applications', [JobApplicationController::class, 'list']);
        Route::post('/applications/{id}/status', [JobApplicationController::class, 'updateStatus']);
    });


Route::middleware('auth:sanctum')->group(function () {
Route::post('/jobs/{jobId}/apply', [JobApplicationController::class, 'apply']);
Route::get('/my-applications', [JobApplicationController::class, 'myApplications']);

});

Route::middleware('auth:sanctum')->group(function () {
        Route::post('/companies/{companyId}/toggle-follow', [FollowController::class, 'toggle']);
        Route::get('/companies/{companyId}/is-following', [FollowController::class, 'isFollowing']);
        Route::get('/companies/{companyId}/followers-count', [FollowController::class, 'followersCount']);
        Route::get('/me/following', [FollowController::class, 'myFollowing']);
        Route::get('/companies/{companyId}/followers', [FollowController::class, 'companyFollowers']);  });
//    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/posts/{postId}/comments', [CommentController::class, 'index']);
        Route::post('/posts/{postId}/comments', [CommentController::class, 'store']);
        Route::post('/comments/{id}', [CommentController::class, 'update']);
        Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
        Route::post('/posts/{postId}/comments/reply', [CommentController::class, 'store']);
        Route::get('/posts/{idPost}/comments/count', [CommentController::class, 'commentsCount']);
        Route::post('/comments/{id}/react', [CommentReactionController::class, 'react']);
        Route::delete('/comments/{id}/react', [CommentReactionController::class, 'remove']);
        Route::get('/comments/{id}/reactions/count', [CommentReactionController::class, 'count']);
        Route::get('/comments/{id}/reactions', [CommentReactionController::class, 'list']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/profile', [ProfileController::class, 'store']);
        Route::post('/profile/update', [ProfileController::class, 'update']);
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::delete('/profile', [ProfileController::class, 'destroy']);
    });

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/skills', [SkillController::class, 'store']);
        Route::get('/skills', [SkillController::class, 'index']);
        Route::post('/skills/{skill}', [SkillController::class, 'update']);
        Route::delete('/skills/{skill}', [SkillController::class, 'destroy']);

    });


    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/experiences', [ExperienceController::class, 'store']);
        Route::get('/experiences', [ExperienceController::class, 'index']);
        Route::get('/experiences/{id}', [ExperienceController::class, 'show']);
        Route::post('/experiences/{id}',[ExperienceController::class, 'update']);
        Route::delete('/experiences/{id}', [ExperienceController::class, 'destroy']);

    });



    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/educations', [EducationController::class, 'store']);
        Route::get('/educations', [EducationController::class, 'index']);
        Route::get('/educations/{id}', [EducationController::class, 'show']);
        Route::post('/educations/{id}', [EducationController::class, 'update']);
        Route::delete('/educations/{id}', [EducationController::class, 'destroy']);

    });


    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/projects', [ProjectController::class, 'store']);
        Route::get('/projects', [ProjectController::class, 'index']);
        Route::get('/projects/{id}', [ProjectController::class, 'show']);
        Route::post('/projects/{id}', [ProjectController::class, 'update']);
        Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);

    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/certifications', [CertificationController::class, 'store']);
        Route::get('/certifications', [CertificationController::class, 'index']);
        Route::get('/certifications/{id}', [CertificationController::class, 'show']);
        Route::post('/certifications/{id}', [CertificationController::class, 'update']);
        Route::delete('/certifications/{id}', [CertificationController::class, 'destroy']);
    });


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/skills/suggest', [SkillSuggestionAIController::class, 'suggest']);
    Route::get('/skills/suggestions', [SkillSuggestionController::class, 'index']);
    Route::post('/skills/suggestions/{suggestion}/accept', [SkillSuggestionController::class, 'accept']);
    Route::post('/skills/suggestions/{suggestion}/reject', [SkillSuggestionController::class, 'reject']);
    Route::delete('/skills/suggestions/{suggestion}', [SkillSuggestionController::class, 'destroy']);
});
Route::middleware('auth:sanctum')->group(function () {

    Route::post(
        '/interviews',
        [InterviewController::class, 'store']
    );

    Route::get(
        '/interviews/{interview}',
        [InterviewController::class, 'show']
    );

    Route::post(
        '/interviews/{interview}/complete',
        [InterviewController::class, 'complete']
    );

    Route::post(
        '/interviews/{interview}/cancel',
        [InterviewController::class, 'cancel']
    );
});
Route::middleware('auth:sanctum')->group(function () {

    Route::post(
        '/application-trainings',
        [ApplicationTrainingController::class, 'store']
    );
    Route::post(
        '/application-trainings/{training}/evaluate',
        [ApplicationTrainingController::class,'evaluate']
    );

});


Route::middleware('auth:sanctum')->group(function () {
        Route::post('/applications/{id}/move-stage',     [WorkflowController::class, 'move']);
        Route::get('/applications/{id}/pipeline',        [WorkflowController::class, 'pipeline']);
        Route::get('/applications/{id}/available-stages',[WorkflowController::class, 'availableStages']);
        Route::post('/applications/{id}/evaluate-rules', [WorkflowController::class, 'evaluate']);
        Route::get('/workflows/{id}/rules',              [WorkflowController::class, 'index']);
        Route::post('/rules',                            [WorkflowController::class, 'store']);
        Route::post('/rules/{id}',                        [WorkflowController::class, 'update']);
        Route::delete('/rules/{id}',                     [WorkflowController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/startup-projects', [StartupProjectController::class, 'store']);
    Route::get('/startup-projects/{id}/suggest-companies', [StartupProjectController::class, 'suggestCompanies']);
    Route::post('/startup-projects/{id}/invite', [StartupProjectController::class, 'invite']);
    Route::post('/startup-projects/{id}/interest', [StartupProjectController::class, 'expressInterest']);
    Route::post('/startup-interests/{interestId}/respond', [StartupProjectController::class, 'respondToInterest']);
    Route::get('/startup-projects/{id}',         [StartupProjectController::class, 'show']);
    Route::post('/startup-projects/{id}/update', [StartupProjectController::class, 'update']);
    Route::delete('/startup-projects/{id}',      [StartupProjectController::class, 'destroy']);
    Route::get('/startup-projects/{id}/interests',[StartupProjectController::class, 'interests']);
});


Route::get('/categories', [CategoryController::class, 'index']);


Route::middleware(['auth:sanctum'])->group(function () {

    // ==========================================
    // 💬 Chat / Conversations
    // ==========================================
    Route::prefix('conversations')->group(function () {
        Route::get('/',                              [ConversationController::class, 'index']);
        Route::get('/unread-count',                  [ConversationController::class, 'unreadCount']);
        Route::get('/{conversation}',                [ConversationController::class, 'show']);
        Route::post('/{conversation}/messages',      [ConversationController::class, 'sendMessage']);
        Route::post('/{conversation}/read',          [ConversationController::class, 'markRead']);
        Route::post('/{conversation}/close',         [ConversationController::class, 'close']);
    });

    // ==========================================
    // ↩️ Withdraw Application
    // ==========================================
    Route::prefix('applications')->group(function () {
        Route::post('/{application}/withdraw',       [WithdrawApplicationController::class, 'withdraw']);
        Route::get('/withdrawals',                   [WithdrawApplicationController::class, 'myWithdrawals']);
        Route::get('/withdraw/reasons',              [WithdrawApplicationController::class, 'reasons']);
    });

    // إحصائيات الانسحاب (للشركة)
    Route::get('/company/applications/withdrawal-stats', [WithdrawApplicationController::class, 'stats'])
        ->middleware('company.approved');

    Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
        Route::get('/',              [NotificationController::class, 'index']);
        Route::get('/unread-count',  [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/read',    [NotificationController::class, 'markAsRead']);
        Route::post('/read-all',     [NotificationController::class, 'markAllAsRead']);
    });

// ==========================================
// ✅ Reviews — عام (يوزر/شركة)
// ==========================================
    Route::middleware('auth:sanctum')->group(function () {

        Route::prefix('reviews')->group(function () {
            Route::get('/{review}',          [ReviewController::class, 'show']);
            Route::post('/{review}/respond', [ReviewController::class, 'respond']);
            Route::post('/{review}/react',   [ReviewController::class, 'react']);
            Route::post('/{review}/flag',    [ReviewController::class, 'flag']);
        });

        Route::get('/users/{id}/reviews', [ReviewController::class, 'userReviews']);
        Route::post('/applications/{application}/review', [ReviewController::class, 'store']);
        Route::get('/companies/{companyUserId}/reviews',   [ReviewController::class, 'companyReviews']);
    });

// ==========================================
// ✅ Reviews — Admin فقط
// ==========================================
    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/reviews')->group(function () {

        Route::post('/{review}/approve', [ReviewController::class, 'approve']);
        Route::post('/{review}/reject',  [ReviewController::class, 'reject']);

        Route::get('/flagged',               [ReviewController::class, 'flaggedReviews']);
        Route::get('/{review}/flags',        [ReviewController::class, 'reviewFlags']);
        Route::post('/flags/{flag}/approve', [ReviewController::class, 'approveFlag']);
        Route::post('/flags/{flag}/dismiss', [ReviewController::class, 'dismissFlag']);
    });
    // ==========================================
    // 📊 Profile Completion
    // ==========================================
    Route::prefix('profile')->group(function () {
        Route::get('/completion',                    [ProfileCompletionController::class, 'index']);
        Route::post('/completion/recalculate',       [ProfileCompletionController::class, 'recalculate']);

        // Public Profile Settings
        Route::get('/public',                        [PublicProfileController::class, 'myProfile']);
        Route::post('/public',                        [PublicProfileController::class, 'update']);
        Route::post('/public/change-slug',           [PublicProfileController::class, 'changeSlug']);
    });

    // ==========================================
    // 🔔 Job Alerts
    // ==========================================
    Route::prefix('job-alerts')->group(function () {
        Route::get('/',                              [JobAlertController::class, 'index']);
        Route::post('/',                             [JobAlertController::class, 'store']);
        Route::post('/{alert}',                       [JobAlertController::class, 'update']);
        Route::post('/{alert}/toggle',               [JobAlertController::class, 'toggle']);
        Route::delete('/{alert}',                    [JobAlertController::class, 'destroy']);
    });

    // ==========================================
    // 🎯 Onboarding
    // ==========================================
    Route::prefix('onboarding')->group(function () {
        Route::get('/',                              [OnboardingController::class, 'index']);
        Route::post('/step/{step}/complete',         [OnboardingController::class, 'completeStep']);
        Route::post('/skip',                         [OnboardingController::class, 'skip']);
        Route::post('/restart',                      [OnboardingController::class, 'restart']);
    });
});

// ==========================================
// 🌐 Public Profile (بدون auth)
// ==========================================
Route::get('/p/{slug}',              [PublicProfileController::class, 'show']);
Route::get('/companies/{id}/reviews', [ReviewController::class, 'companyReviews']);



// routes/api.php
Route::get('/salary-insights', [SalaryInsightController::class, 'index']);
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reports', [ReportController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/reports')->group(function () {
    Route::get('/', [ReportController::class, 'index']);
    Route::post('/{report}/resolve', [ReportController::class, 'resolve']); // body: { status, admin_note }
});
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/blocks', [BlockController::class, 'store']);     // body: { blockable_type: user|company, blockable_id }
    Route::delete('/blocks/{id}', [BlockController::class, 'destroy']);
    Route::get('/blocks', [BlockController::class, 'index']);
});
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/account/export', [DataExportController::class, 'store']);
    Route::get('/account/export/{req}/download', [DataExportController::class, 'download']);
    Route::get('/account/export/status', [DataExportController::class, 'status']);
});
