<?php

namespace App\Http\Controllers\Resources;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Cache; 
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use App\Http\Discuss\ContextFactory;
use App\Adapters\DiscussAdapter;
use App\Models\Initialization\Morphs;
use App\Events\ForumPostCreated;
use App\Repositories\{
    DiscussRepository,
    StatisticsRepository
};
use App\Helpers\{
    LinkHelper,
    StringHelper
};
use App\Models\{
    Account,
    ForumDiscussion,
    ForumGroup,
    ForumThread,
    ForumPost
};

class DiscussController extends Controller
{
    protected $_discussAdapter;
    protected $_contextFactory;
    protected $_discussRepository;
    protected $_statisticsRepository;

    public function __construct(DiscussAdapter $discussAdapter, ContextFactory $contextFactory,
        DiscussRepository $discussRepository, StatisticsRepository $statisticsRepository) 
    {
        $this->_discussAdapter       = $discussAdapter;
        $this->_discussRepository    = $discussRepository;
        $this->_contextFactory       = $contextFactory;
        $this->_statisticsRepository = $statisticsRepository;
    }

    public function index(Request $request)
    {
        return $this->groups($request);
    }

    public function groups(Request $request)
    {
        $model = $this->_discussRepository->getGroups();
        return view('discuss.groups', $model);
    }

    public function group(Request $request, int $id)
    {
        $currentPage = max(0, intval($request->input('offset')));

        $groupData = $this->_discussRepository->getGroup($id);
        $model = $this->_discussRepository->getThreadsInGroup($groupData['group'], $request->user(), $currentPage);
        
        return view('discuss.group', $model);
    }

    public function show(Request $request, int $groupId, string $groupSlug, int $id)
    {
        $currentPage = max(0, intval($request->get('offset')));
        $forumPostId = intval($request->get('forum_post_id'));

        $groupData = $this->_discussRepository->getGroup($groupId);
        $threadData = $this->_discussRepository->getThread($id);
        $posts = $this->_discussRepository->getPostsInThread($threadData['thread'], $request->user(), 'asc', $currentPage, $forumPostId);

        return view('discuss.thread', $threadData + $groupData + [
            'preloadedPosts' => $posts
        ]);
    }

    public function topMembers(Request $request)
    {
        $cacheTtlInMinutes = 30;
        $data = Cache::remember('discuss.top-members', $cacheTtlInMinutes, function () use($cacheTtlInMinutes) {
            return array_merge(
                $this->_statisticsRepository->getContributors(),
                [ 
                    'created_at' => Carbon::now(), 
                    'expires_at' => Carbon::now()->addMinutes($cacheTtlInMinutes) 
                ]
            );
        });
        
        return view('discuss.member-top-list', ['data' => $data]);
    }

    public function allMembers(Request $request)
    {
        $members = Account::orderBy('nickname', 'asc')
            ->paginate(30);

        return view('discuss.member-all-list', ['members' => $members]);
    }

    public function create(Request $request)
    {
        return view('discuss.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'subject' => 'required|string|min:3',
            'content' => 'required|string|min:3'
        ]);

        $userId = $request->user()->id;

        // Create a discussion which will be the entity associated with
        // the thread.
        $discussion = ForumDiscussion::create([
            'account_id' => $userId
        ]);
        $typeName = Morphs::getAlias($discussion);

        // Create a forum thread for the previously created discussion.
        $thread = ForumThread::create([
            'entity_type'        => $typeName,
            'entity_id'          => $discussion->id,
            'subject'            => $request->input('subject'),
            'normalized_subject' => StringHelper::normalize($request->input('subject')),
            'account_id'         => $userId,
            'number_of_posts'    => 1
        ]);

        // Create a post with the user's message content
        $post = ForumPost::create([
            'account_id'      => $userId,
            'forum_thread_id' => $thread->id,
            'content'         => $request->input('content')
        ]);

        event(new ForumPostCreated($post, $userId));

        $linker = new LinkHelper();
        return redirect($linker->forumThread($thread->id, $thread->normalized_subject));
    }

    public function resolveThread(Request $request, int $id)
    {
        $discuss = ForumDiscussion::findOrFail($id);
        if ($discuss === null) {
            abort(404, 'The discussion does not exist.');
        }

        $linker = new LinkHelper();
        return redirect($linker->forumThread($discuss->forum_thread->id, $discuss->forum_thread->normalized_subject));
    }
}
