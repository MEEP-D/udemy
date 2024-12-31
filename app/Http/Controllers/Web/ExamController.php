<?php

namespace App\Http\Controllers\Web;

use App\Exports\QuizResultsExport;
use App\Exports\QuizzesAdminExport;
use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizzesQuestion;
use App\Models\QuizzesResult;
use App\Models\Translation\QuizTranslation;
use App\Models\Section;
use App\Models\Answer;
use App\Models\Group;
use App\Models\Webinar;
use App\Models\Question;
use App\Models\WebinarChapter;
use App\Models\WebinarChapterItem;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(Request $request)
{
    removeContentLocale();

    $query = Quiz::query();

    // Các biến khác
    $totalQuizzes = deepClone($query)->count();
    $totalActiveQuizzes = deepClone($query)->where('status', 'active')->count();
    $totalStudents = QuizzesResult::groupBy('user_id')->count();
    $totalPassedStudents = QuizzesResult::where('status', 'passed')->groupBy('user_id')->count();

    // Lọc quiz
    $query = $this->filters($query, $request);

    // Lấy danh sách quiz
    $quizzes = $query->with([
        'webinar',
        'teacher',
        'quizQuestions',
        'quizResults',
    ])->paginate(10);

    // Lấy danh sách câu hỏi (nếu cần)
    $questions = QuizzesQuestion::all(); // Hoặc một truy vấn phù hợp với yêu cầu của bạn

    // Lấy danh sách nhóm (nếu cần)
    $groups = Group::all(); // Hoặc một truy vấn phù hợp với yêu cầu của bạn

    $data = [
        'pageTitle' => trans('admin/pages/quiz.admin_quizzes_list'),
        'quizzes' => $quizzes,
        'totalQuizzes' => $totalQuizzes,
        'totalActiveQuizzes' => $totalActiveQuizzes,
        'totalStudents' => $totalStudents,
        'totalPassedStudents' => $totalPassedStudents,
        'questions' => $questions, // Truyền biến questions đến view
        'groups' => $groups, // Truyền biến groups đến view
    ];

    // Các biến khác
    $teacher_ids = $request->get('teacher_ids');
    $webinar_ids = $request->get('webinar_ids');

    if (!empty($teacher_ids)) {
        $data['teachers'] = User::select('id', 'full_name')
            ->whereIn('id', $teacher_ids)->get();
    }

    if (!empty($webinar_ids)) {
        $data['webinars'] = Webinar::select('id', 'title')
            ->whereIn('id', $webinar_ids)->get();
    }

    return view('web.default.includes.webinar.exam', $data);
}



    private function filters($query, $request)
    {
        $from = $request->get('from', null);
        $to = $request->get('to', null);
        $title = $request->get('title', null);
        $sort = $request->get('sort', null);
        $teacher_ids = $request->get('teacher_ids', null);
        $webinar_ids = $request->get('webinar_ids', null);
        $status = $request->get('status', null);

        $query = fromAndToDateFilter($from, $to, $query, 'created_at');

        if (!empty($title)) {
            $query->whereTranslationLike('title', '%' . $title . '%');
        }

        if (!empty($sort)) {
            switch ($sort) {
                case 'have_certificate':
                    $query->where('certificate', true);
                    break;
                case 'students_count_asc':
                    $query->join('quizzes_results', 'quizzes_results.quiz_id', '=', 'quizzes.id')
                        ->select('quizzes.*', 'quizzes_results.quiz_id', DB::raw('count(quizzes_results.quiz_id) as result_count'))
                        ->groupBy('quizzes_results.quiz_id')
                        ->orderBy('result_count', 'asc');
                    break;

                case 'students_count_desc':
                    $query->join('quizzes_results', 'quizzes_results.quiz_id', '=', 'quizzes.id')
                        ->select('quizzes.*', 'quizzes_results.quiz_id', DB::raw('count(quizzes_results.quiz_id) as result_count'))
                        ->groupBy('quizzes_results.quiz_id')
                        ->orderBy('result_count', 'desc');
                    break;
                case 'passed_count_asc':
                    $query->join('quizzes_results', 'quizzes_results.quiz_id', '=', 'quizzes.id')
                        ->select('quizzes.*', 'quizzes_results.quiz_id', DB::raw('count(quizzes_results.quiz_id) as result_count'))
                        ->where('quizzes_results.status', 'passed')
                        ->groupBy('quizzes_results.quiz_id')
                        ->orderBy('result_count', 'asc');
                    break;

                case 'passed_count_desc':
                    $query->join('quizzes_results', 'quizzes_results.quiz_id', '=', 'quizzes.id')
                        ->select('quizzes.*', 'quizzes_results.quiz_id', DB::raw('count(quizzes_results.quiz_id) as result_count'))
                        ->where('quizzes_results.status', 'passed')
                        ->groupBy('quizzes_results.quiz_id')
                        ->orderBy('result_count', 'desc');
                    break;

                case 'grade_avg_asc':
                    $query->join('quizzes_results', 'quizzes_results.quiz_id', '=', 'quizzes.id')
                        ->select('quizzes.*', 'quizzes_results.quiz_id', 'quizzes_results.user_grade', DB::raw('avg(quizzes_results.user_grade) as grade_avg'))
                        ->groupBy('quizzes_results.quiz_id')
                        ->orderBy('grade_avg', 'asc');
                    break;

                case 'grade_avg_desc':
                    $query->join('quizzes_results', 'quizzes_results.quiz_id', '=', 'quizzes.id')
                        ->select('quizzes.*', 'quizzes_results.quiz_id', 'quizzes_results.user_grade', DB::raw('avg(quizzes_results.user_grade) as grade_avg'))
                        ->groupBy('quizzes_results.quiz_id')
                        ->orderBy('grade_avg', 'desc');
                    break;

                case 'created_at_asc':
                    $query->orderBy('created_at', 'asc');
                    break;

                case 'created_at_desc':
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if (!empty($teacher_ids)) {
            $query->whereIn('creator_id', $teacher_ids);
        }

        if (!empty($webinar_ids)) {
            $query->whereIn('webinar_id', $webinar_ids);
        }

        if (!empty($status) and $status !== 'all') {
            $query->where('status', strtolower($status));
        }

        return $query;
    }

    public function create()
    {
        $this->authorize('admin_quizzes_create');

        $data = [
            'pageTitle' => trans('quiz.new_quiz'),
        ];

        return view('admin.quizzes.create', $data);
    }

    public function store(Request $request)
    {
        $this->authorize('admin_quizzes_create');

        $data = $request->get('ajax')['new'];
        $locale = $data['locale'] ?? getDefaultLocale();

        $rules = [
            'title' => 'required|max:255',
            'webinar_id' => 'required|exists:webinars,id',
            'pass_mark' => 'required',
        ];

        $validate = Validator::make($data, $rules);

        if ($validate->fails()) {
            return response()->json([
                'code' => 422,
                'errors' => $validate->errors()
            ], 422);
        }

        $webinar = Webinar::where('id', $data['webinar_id'])->first();

        if (!empty($webinar)) {
            $chapter = null;

            if (!empty($data['chapter_id'])) {
                $chapter = WebinarChapter::where('id', $data['chapter_id'])
                    ->where('webinar_id', $webinar->id)
                    ->first();
            }

            $quiz = Quiz::create([
                'webinar_id' => $webinar->id,
                'chapter_id' => !empty($chapter) ? $chapter->id : null,
                'creator_id' => $webinar->creator_id,
                'attempt' => $data['attempt'] ?? null,
                'pass_mark' => $data['pass_mark'],
                'time' => $data['time'] ?? null,
                'status' => (!empty($data['status']) and $data['status'] == 'on') ? Quiz::ACTIVE : Quiz::INACTIVE,
                'certificate' => (!empty($data['certificate']) and $data['certificate'] == 'on'),
                'display_questions_randomly' => (!empty($data['display_questions_randomly']) and $data['display_questions_randomly'] == 'on'),
                'expiry_days' => (!empty($data['expiry_days']) and $data['expiry_days'] > 0) ? $data['expiry_days'] : null,
                'created_at' => time(),
            ]);

            QuizTranslation::updateOrCreate([
                'quiz_id' => $quiz->id,
                'locale' => mb_strtolower($locale),
            ], [
                'title' => $data['title'],
            ]);

            if (!empty($quiz->chapter_id)) {
                WebinarChapterItem::makeItem($webinar->creator_id, $quiz->chapter_id, $quiz->id, WebinarChapterItem::$chapterQuiz);
            }

            // Send Notification To All Students
            $webinar->sendNotificationToAllStudentsForNewQuizPublished($quiz);

            if ($request->ajax()) {
                $redirectUrl = '';

                if (empty($data['is_webinar_page'])) {
                    $redirectUrl = getAdminPanelUrl('/quizzes/' . $quiz->id . '/edit');
                }

                return response()->json([
                    'code' => 200,
                    'redirect_url' => $redirectUrl
                ]);
            } else {
                return redirect()->route('adminEditQuiz', ['id' => $quiz->id]);
            }
        } else {
            return back()->withErrors([
                'webinar_id' => trans('validation.exists', ['attribute' => trans('admin/main.course')])
            ]);
        }
    }

    public function edit(Request $request, $id)
    {
        $this->authorize('admin_quizzes_edit');

        $quiz = Quiz::query()->where('id', $id)
            ->with([
                'quizQuestions' => function ($query) {
                    $query->orderBy('order', 'asc');
                    $query->with('quizzesQuestionsAnswers');
                },
            ])
            ->first();

        if (empty($quiz)) {
            abort(404);
        }

        $creator = $quiz->creator;

        $webinars = Webinar::where('status', 'active')
            ->where(function ($query) use ($creator) {
                $query->where('teacher_id', $creator->id)
                    ->orWhere('creator_id', $creator->id);
            })->get();

        $locale = $request->get('locale', app()->getLocale());
        if (empty($locale)) {
            $locale = app()->getLocale();
        }
        storeContentLocale($locale, $quiz->getTable(), $quiz->id);

        $quiz->title = $quiz->getTitleAttribute();
        $quiz->locale = mb_strtoupper($locale);

        $chapters = collect();

        if (!empty($quiz->webinar)) {
            $chapters = $quiz->webinar->chapters;
        }

        $data = [
            'pageTitle' => trans('public.edit') . ' ' . $quiz->title,
            'webinars' => $webinars,
            'quiz' => $quiz,
            'quizQuestions' => $quiz->quizQuestions,
            'creator' => $creator,
            'chapters' => $chapters,
            'locale' => mb_strtolower($locale),
            'defaultLocale' => getDefaultLocale(),
        ];

        return view('admin.quizzes.edit', $data);
    }

    public function update(Request $request, $id)
    {
        $quiz = Quiz::query()->findOrFail($id);
        $user = $quiz->creator;
        $quizQuestionsCount = $quiz->quizQuestions->count();

        $data = $request->get('ajax')[$id];
        $locale = $data['locale'] ?? getDefaultLocale();

        $rules = [
            'title' => 'required|max:255',
            'webinar_id' => 'required|exists:webinars,id',
            'pass_mark' => 'required',
            'display_number_of_questions' => 'required_if:display_limited_questions,on|nullable|between:1,' . $quizQuestionsCount
        ];

        $validate = Validator::make($data, $rules);

        if ($validate->fails()) {
            return response()->json([
                'code' => 422,
                'errors' => $validate->errors()
            ], 422);
        }

        $webinar = null;
        $chapter = null;

        if (!empty($data['webinar_id'])) {
            $webinar = Webinar::where('id', $data['webinar_id'])->first();

            if (!empty($webinar) and !empty($data['chapter_id'])) {
                $chapter = WebinarChapter::where('id', $data['chapter_id'])
                    ->where('webinar_id', $webinar->id)
                    ->first();
            }
        }

        $quiz->update([
            'webinar_id' => !empty($webinar) ? $webinar->id : null,
            'chapter_id' => !empty($chapter) ? $chapter->id : null,
            'attempt' => $data['attempt'] ?? null,
            'pass_mark' => $data['pass_mark'],
            'time' => $data['time'] ?? null,
            'status' => (!empty($data['status']) and $data['status'] == 'on') ? Quiz::ACTIVE : Quiz::INACTIVE,
            'certificate' => (!empty($data['certificate']) and $data['certificate'] == 'on'),
            'display_limited_questions' => (!empty($data['display_limited_questions']) and $data['display_limited_questions'] == 'on'),
            'display_number_of_questions' => (!empty($data['display_limited_questions']) and $data['display_limited_questions'] == 'on' and !empty($data['display_number_of_questions'])) ? $data['display_number_of_questions'] : null,
            'display_questions_randomly' => (!empty($data['display_questions_randomly']) and $data['display_questions_randomly'] == 'on'),
            'expiry_days' => (!empty($data['expiry_days']) and $data['expiry_days'] > 0) ? $data['expiry_days'] : null,
            'updated_at' => time(),
        ]);

        if (!empty($quiz)) {
            QuizTranslation::updateOrCreate([
                'quiz_id' => $quiz->id,
                'locale' => mb_strtolower($locale),
            ], [
                'title' => $data['title'],
            ]);

            $checkChapterItem = WebinarChapterItem::where('user_id', $user->id)
                ->where('item_id', $quiz->id)
                ->where('type', WebinarChapterItem::$chapterQuiz)
                ->first();

            if (!empty($quiz->chapter_id)) {
                if (empty($checkChapterItem)) {
                    WebinarChapterItem::makeItem($user->id, $quiz->chapter_id, $quiz->id, WebinarChapterItem::$chapterQuiz);
                } elseif ($checkChapterItem->chapter_id != $quiz->chapter_id) {
                    $checkChapterItem->delete(); // remove quiz from old chapter and assign it to new chapter

                    WebinarChapterItem::makeItem($user->id, $quiz->chapter_id, $quiz->id, WebinarChapterItem::$chapterQuiz);
                }
            } else if (!empty($checkChapterItem)) {
                $checkChapterItem->delete();
            }
        }

        // Cập nhật các Section, Group, Question, và Answer
        DB::transaction(function () use ($request, $quiz) {
            $sectionIds = collect($request->sections)->pluck('id')->filter();
            foreach ($request->sections as $sectionData) {
                $section = Section::updateOrCreate(
                    ['id' => $sectionData['id']],
                    ['name' => $sectionData['name'], 'quiz_id' => $quiz->id]
                );

                $groupIds = collect($sectionData['groups'])->pluck('id')->filter();
                foreach ($sectionData['groups'] as $groupData) {
                    $group = Group::updateOrCreate(
                        ['id' => $groupData['id']],
                        ['name' => $groupData['name'], 'section_id' => $section->id]
                    );

                    $questionIds = collect($groupData['questions'])->pluck('id')->filter();
                    foreach ($groupData['questions'] as $questionData) {
                        $question = Question::updateOrCreate(
                            ['id' => $questionData['id']],
                            ['content' => $questionData['content'], 'group_id' => $group->id]
                        );

                        $answerIds = collect($questionData['answers'])->pluck('id')->filter();
                        foreach ($questionData['answers'] as $answerData) {
                            Answer::updateOrCreate(
                                ['id' => $answerData['id']],
                                [
                                    'content' => $answerData['content'],
                                    'is_correct' => $answerData['is_correct'],
                                    'question_id' => $question->id
                                ]
                            );
                        }
                        Answer::where('question_id', $question->id)->whereNotIn('id', $answerIds)->delete();
                    }
                    Question::where('group_id', $group->id)->whereNotIn('id', $questionIds)->delete();
                }
                Group::where('section_id', $section->id)->whereNotIn('id', $groupIds)->delete();
            }
            Section::where('quiz_id', $quiz->id)->whereNotIn('id', $sectionIds)->delete();
        });

        removeContentLocale();

        if ($request->ajax()) {
            return response()->json([
                'code' => 200
            ]);
        } else {
            return redirect()->back();
        }
    }

    public function delete(Request $request, $id)
    {
        $this->authorize('admin_quizzes_delete');

        $quiz = Quiz::findOrFail($id);

        $quiz->delete();

        $checkChapterItem = WebinarChapterItem::where('item_id', $id)
            ->where('type', WebinarChapterItem::$chapterQuiz)
            ->first();

        if (!empty($checkChapterItem)) {
            $checkChapterItem->delete();
        }

        if ($request->ajax()) {
            return response()->json([
                'code' => 200
            ], 200);
        }

        return redirect()->back();
    }

    public function results($id)
    {
        $this->authorize('admin_quizzes_results');

        $quizzesResults = QuizzesResult::where('quiz_id', $id)
            ->with([
                'quiz' => function ($query) {
                    $query->with(['teacher']);
                },
                'user'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $data = [
            'pageTitle' => trans('admin/pages/quizResults.quiz_result_list_page_title'),
            'quizzesResults' => $quizzesResults,
            'quiz_id' => $id
        ];

        return view('admin.quizzes.results', $data);
    }

    public function resultsExportExcel($id)
    {
        $this->authorize('admin_quiz_result_export_excel');

        $quizzesResults = QuizzesResult::where('quiz_id', $id)
            ->with([
                'quiz' => function ($query) {
                    $query->with(['teacher']);
                },
                'user'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $export = new QuizResultsExport($quizzesResults);

        return Excel::download($export, 'quiz_result.xlsx');
    }

    public function resultDelete($result_id)
    {
        $this->authorize('admin_quizzes_results_delete');

        $quizzesResults = QuizzesResult::where('id', $result_id)->first();

        if (!empty($quizzesResults)) {
            $quizzesResults->delete();
        }

        return redirect()->back();
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
        ]);

        $path = $request->file('file')->getRealPath();
        $data = array_map(function($line) {
            return str_getcsv($line, ",");
        }, file($path));

        foreach ($data as $row) {
            // Tạo hoặc lấy quiz
            $quiz = Quiz::updateOrCreate(
                ['title' => $row[0] ?? 'Untitled Quiz', 'creator_id' => auth()->id()],
                [
                    'info' => $row[1] ?? null,
                    'url' => $row[2] ?? null,
                    'certificate' => rand(0, 1),
                    'pass_mark' => rand(1, 100),
                    'total_mark' => rand(1, 100),
                    'created_at' => time(),
                ]
            );

            // Tạo hoặc lấy section (liên kết với quiz)
            if (!empty($row[3])) {
                $section = Section::updateOrCreate(
                    ['title' => $row[3], 'quiz_id' => $quiz->id], // Liên kết với quiz
                    [
                        'created_at' => time(),
                    ]
                );
            }

            // Tạo hoặc lấy group (liên kết với section)
            if (!empty($row[5])) {
                $group = Group::updateOrCreate(
                    ['title' => $row[5], 'section_id' => $section->id ?? null],
                    [
                        'info' => $row[6] ?? null,
                        'creator_id' => auth()->id(),
                        'created_at' => time(),
                    ]
                );
            }

            // Tạo hoặc lấy question (liên kết với group)
            if (!empty($row[7])) {
                $question = Question::updateOrCreate(
                    ['title' => $row[7],'group_id' => $group->id ?? null ],
                    [
                        'content' => $row[8] ?? null,
                        'mean' => $row[12] ?? null,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]
                );
            }

            // Tạo hoặc lấy answer (liên kết với question)
            if (!empty($row[13])) {
                $answer = Answer::updateOrCreate(
                    ['content' => $row[13], 'question_id' => $question->id ?? null],
                    [
                        'is_correct' => strtolower($row[14]) === 'true' ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        return redirect()->route('admin.quizzes.import')->with('success', 'Quizzes imported successfully.');
    }

    public function exportExcel(Request $request)
    {
        $this->authorize('admin_quizzes_lists_excel');

        $query = Quiz::query();

        $query = $this->filters($query, $request);

        $quizzes = $query->with([
            'webinar',
            'teacher',
            'quizQuestions',
            'quizResults',
        ])->get();

        return Excel::download(new QuizzesAdminExport($quizzes), trans('quiz.quizzes') . '.xlsx');
    }

    public function orderItems(Request $request, $quizId)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'items' => 'required',
            'table' => 'required',
        ]);

        if ($validator->fails()) {
            return response([
                'code' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $quiz = Quiz::query()->where('id', $quizId)->first();

        if (!empty($quiz)) {
            $tableName = $data['table'];
            $itemIds = explode(',', $data['items']);

            if (!is_array($itemIds) and !empty($itemIds)) {
                $itemIds = [$itemIds];
            }

            if (!empty($itemIds) and is_array($itemIds) and count($itemIds)) {
                switch ($tableName) {
                    case 'quizzes_questions':
                        foreach ($itemIds as $order => $id) {
                            QuizzesQuestion::where('id', $id)
                                ->where('quiz_id', $quiz->id)
                                ->update(['order' => ($order + 1)]);
                        }
                        break;
                }
            }
        }

        return response()->json([
            'title' => trans('public.request_success'),
            'msg' => trans('update.items_sorted_successful')
        ]);
    }
}
