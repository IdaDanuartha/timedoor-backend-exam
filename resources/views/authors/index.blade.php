@extends('layouts.app')

@section('title', 'Top 20 Most Famous Authors')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Top 20 Most Famous Authors</h1>
        <p class="mt-2 text-gray-600">Discover the most popular and highly-rated authors in our collection</p>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow-md mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <a href="{{ route('authors.top', ['tab' => 'popularity']) }}" 
                   class="@if($tab == 'popularity') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                    <i class="fas fa-fire mr-2"></i> By Popularity
                </a>
                <a href="{{ route('authors.top', ['tab' => 'rating']) }}" 
                   class="@if($tab == 'rating') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                    <i class="fas fa-star mr-2"></i> By Average Rating
                </a>
                <a href="{{ route('authors.top', ['tab' => 'trending']) }}" 
                   class="@if($tab == 'trending') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                    <i class="fas fa-arrow-trend-up mr-2"></i> Trending
                </a>
            </nav>
        </div>

        <div class="p-6">
            @if($tab == 'popularity')
            <p class="text-gray-600 mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                Ranked by number of votes with ratings above 5 (indicating positive reception)
            </p>
            @elseif($tab == 'rating')
            <p class="text-gray-600 mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                Ranked by overall average rating across all their books
            </p>
            @else
            <p class="text-gray-600 mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                Ranked by trending score: (Recent Month Avg - Previous Month Avg) × log(Recent Voter Count)
            </p>
            @endif
        </div>
    </div>

    <!-- Authors List -->
    <div class="space-y-4">
        @foreach($authors as $index => $author)
        <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-start">
                <!-- Rank Badge -->
                <div class="flex-shrink-0 mr-6">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold
                                @if($index == 0) bg-yellow-400 text-white
                                @elseif($index == 1) bg-gray-300 text-white
                                @elseif($index == 2) bg-orange-400 text-white
                                @else bg-blue-100 text-blue-600 @endif">
                        {{ $index + 1 }}
                    </div>
                </div>

                <!-- Author Info -->
                <div class="flex-1">
                    <h3 class="text-2xl font-bold text-gray-900">{{ $author->name }}</h3>
                    
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @if($tab == 'popularity')
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-600">Popularity Votes</div>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ number_format($author->popularity_votes) }}
                            </div>
                            <div class="text-xs text-gray-500">Ratings > 5</div>
                        </div>
                        <div class="bg-green-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-600">Average Rating</div>
                            <div class="text-2xl font-bold text-green-600">
                                {{ number_format($author->avg_rating, 2) }}/10
                            </div>
                        </div>
                        <div class="bg-purple-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-600">Total Ratings</div>
                            <div class="text-2xl font-bold text-purple-600">
                                {{ number_format($author->total_ratings) }}
                            </div>
                        </div>
                        @elseif($tab == 'rating')
                        <div class="bg-green-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-600">Average Rating</div>
                            <div class="text-2xl font-bold text-green-600">
                                {{ number_format($author->avg_rating, 2) }}/10
                            </div>
                        </div>
                        <div class="bg-purple-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-600">Total Ratings</div>
                            <div class="text-2xl font-bold text-purple-600">
                                {{ number_format($author->total_ratings) }}
                            </div>
                        </div>
                        @else
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-600">Trending Score</div>
                            <div class="text-2xl font-bold text-orange-600">
                                {{ number_format($author->trending_score ?? 0, 2) }}
                            </div>
                        </div>
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-600">Recent Average</div>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ number_format($author->recent_avg ?? 0, 2) }}/10
                            </div>
                            <div class="text-xs text-gray-500">Last 30 days</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-600">Previous Average</div>
                            <div class="text-2xl font-bold text-gray-600">
                                {{ number_format($author->previous_avg ?? 0, 2) }}/10
                            </div>
                            <div class="text-xs text-gray-500">30-60 days ago</div>
                        </div>
                        <div class="bg-purple-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-600">Recent Votes</div>
                            <div class="text-2xl font-bold text-purple-600">
                                {{ number_format($author->recent_count ?? 0) }}
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Best & Worst Books -->
                    @if(isset($author->best_book))
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border-l-4 border-green-500 pl-4">
                            <div class="text-sm text-gray-600 font-medium">
                                <i class="fas fa-trophy text-green-500 mr-1"></i> Best Rated Book
                            </div>
                            <div class="text-gray-900 font-medium mt-1">{{ $author->best_book }}</div>
                            <div class="text-green-600 font-bold">
                                {{ number_format($author->best_book_rating, 2) }}/10
                            </div>
                        </div>
                        <div class="border-l-4 border-red-500 pl-4">
                            <div class="text-sm text-gray-600 font-medium">
                                <i class="fas fa-chart-line text-red-500 mr-1"></i> Worst Rated Book
                            </div>
                            <div class="text-gray-900 font-medium mt-1">{{ $author->worst_book }}</div>
                            <div class="text-red-600 font-bold">
                                {{ number_format($author->worst_book_rating, 2) }}/10
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($authors->isEmpty())
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <i class="fas fa-user-slash text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-500 text-lg">No authors found for this ranking.</p>
    </div>
    @endif
</div>
@endsection