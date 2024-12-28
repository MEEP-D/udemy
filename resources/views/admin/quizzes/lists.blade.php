@extends('admin.layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1>{{ trans('admin/main.quizzes') }}</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="/admin/">{{ trans('admin/main.dashboard') }}</a></div>
            <div class="breadcrumb-item">{{ trans('admin/main.quizzes') }}</div>
        </div>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-primary">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>{{ trans('admin/main.total_quizzes') }}</h4>
                        </div>
                        <div class="card-body">
                            {{ $totalQuizzes }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>{{ trans('admin/main.active_quizzes') }}</h4>
                        </div>
                        <div class="card-body">
                            {{ $totalActiveQuizzes }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-warning">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>{{ trans('admin/main.total_students') }}</h4>
                        </div>
                        <div class="card-body">
                            {{ $totalStudents }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-danger">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>{{ trans('admin/main.passed_students') }}</h4>
                        </div>
                        <div class="card-body">
                            {{ $totalPassedStudents }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ trans('admin/main.quizzes') }}</h4>
                        <div class="card-header-action">
                            <a href="{{ url('/admin/quizzes/create') }}" class="btn btn-primary">{{ trans('admin/main.create') }}</a>
                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#importModal">
                                {{ trans('admin/main.import_csv') }}
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($quizzes->isEmpty())
                            <div class="empty-state" data-height="400">
                                <img class="img-fluid" src="{{ asset('img/empty.svg') }}" alt="image">
                                <h2>{{ trans('admin/main.no_result') }}</h2>
                                <p class="lead">
                                    {{ trans('admin/main.no_result_hint') }}
                                </p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ trans('admin/main.title') }}</th>
                                            <th>{{ trans('admin/main.creator') }}</th>
                                            <th>{{ trans('admin/main.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($quizzes as $quiz)
                                            <tr>
                                                <td>{{ $quiz->title }}</td>
                                                <td>{{ $quiz->teacher ? $quiz->teacher->full_name : 'N/A' }}</td>
                                                <td>
                                                    <a href="{{ route('adminEditQuiz', $quiz->id) }}" class="btn btn-primary">{{ trans('admin/main.edit') }}</a>
                                                    <a href="{{ url('admin/quizzes/'.$quiz->id.'/delete') }}" class="btn btn-danger">{{ trans('admin/main.delete') }}</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="3">
                                                    <div class="quiz-details">
                                                        <h5>Quiz Details</h5>
                                                        <p><strong>Info:</strong> {{ $quiz->info }}</p>
                                                        <p><strong>URL:</strong> {{ $quiz->url }}</p>
                                                        <p><strong>Certificate:</strong> {{ $quiz->certificate ? 'Yes' : 'No' }}</p>
                                                        <p><strong>Pass Mark:</strong> {{ $quiz->pass_mark }}</p>
                                                        <p><strong>Total Mark:</strong> {{ $quiz->total_mark }}</p>

                                                        @foreach($quiz->sections as $section)
                                                            <div class="section">
                                                                <h6>Section: {{ $section->title }}</h6>

                                                                @foreach($section->groups as $group)
                                                                    <div class="group">
                                                                        <h6>Group: {{ $group->title }}</h6>
                                                                        <p><strong>Info:</strong> {{ $group->info }}</p>

                                                                        @foreach($group->questions as $question)
                                                                            <div class="question">
                                                                                <h6>Question: {{ $question->title }}</h6>
                                                                                <p><strong>Content:</strong> {{ $question->content }}</p>
                                                                                <p><strong>Mean:</strong> {{ $question->mean }}</p>

                                                                                @foreach($question->answers as $answer)
                                                                                    <div class="answer">
                                                                                        <p><strong>Answer:</strong> {{ $answer->content }}</p>
                                                                                        <p><strong>Correct:</strong> {{ $answer->is_correct ? 'Correct' : 'Incorrect' }}</p>
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer text-center">
                        {{ $quizzes->appends(request()->input())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Form upload file CSV -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">{{ trans('admin/main.import_csv') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.quizzes.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="file">{{ trans('admin/main.upload_csv_file') }}</label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('admin/main.close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ trans('admin/main.import') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection