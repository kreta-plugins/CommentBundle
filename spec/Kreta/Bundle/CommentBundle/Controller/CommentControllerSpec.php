<?php

/*
 * This file is part of the Kreta package.
 *
 * (c) Beñat Espiña <benatespina@gmail.com>
 * (c) Gorka Laucirica <gorka.lauzirika@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Kreta\Bundle\CommentBundle\Controller;

use FOS\RestBundle\Request\ParamFetcher;
use Kreta\Component\Comment\Model\Interfaces\CommentInterface;
use Kreta\Component\Comment\Repository\CommentRepository;
use Kreta\Component\Core\Form\Handler\Handler;
use Kreta\Component\Issue\Model\Interfaces\IssueInterface;
use Kreta\Component\User\Model\Interfaces\UserInterface;
use PhpSpec\ObjectBehavior;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Spec of CommentController class.
 *
 * @author Beñat Espiña <benatespina@gmail.com>
 * @author Gorka Laucirica <gorka.lauzirika@gmail.com>
 */
class CommentControllerSpec extends ObjectBehavior
{
    function let(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Kreta\Bundle\CommentBundle\Controller\CommentController');
    }

    function it_extends_controller()
    {
        $this->shouldHaveType('Symfony\Bundle\FrameworkBundle\Controller\Controller');
    }

    function it_gets_comments(
        ContainerInterface $container,
        CommentRepository $commentRepository,
        Request $request,
        ParamFetcher $paramFetcher,
        IssueInterface $issue,
        CommentInterface $comment
    ) {
        $container->get('kreta_comment.repository.comment')->shouldBeCalled()->willReturn($commentRepository);
        $request->get('issue')->shouldBeCalled()->willReturn($issue);
        $paramFetcher->get('createdAt')->shouldBeCalled()->willReturn('2014-10-20');
        $paramFetcher->get('owner')->shouldBeCalled()->willReturn('user@kreta.com');
        $paramFetcher->get('limit')->shouldBeCalled()->willReturn(10);
        $paramFetcher->get('offset')->shouldBeCalled()->willReturn(1);

        $commentRepository->findByIssue(
            $issue, new \DateTime('2014-10-20'), 'user@kreta.com', 10, 1
        )->shouldBeCalled()->willReturn([$comment]);

        $this->getCommentsAction($request, 'issue-id', $paramFetcher)->shouldReturn([$comment]);
    }

    function it_posts_comment(
        ContainerInterface $container,
        Handler $handler,
        Request $request,
        IssueInterface $issue,
        CommentInterface $comment
    ) {
        $container->get('kreta_comment.form_handler.comment')->shouldBeCalled()->willReturn($handler);
        $request->get('issue')->shouldBeCalled()->willReturn($issue);
        $handler->processForm($request, null, ['issue' => $issue])->shouldBeCalled()->willReturn($comment);

        $this->postCommentsAction($request, 'issue-id')->shouldReturn($comment);
    }

    function it_puts_comment(
        ContainerInterface $container,
        CommentRepository $commentRepository,
        Handler $handler,
        IssueInterface $issue,
        CommentInterface $comment,
        TokenStorageInterface $context,
        TokenInterface $token,
        UserInterface $user,
        Request $request
    ) {
        $container->get('kreta_comment.repository.comment')->shouldBeCalled()->willReturn($commentRepository);

        $container->has('security.token_storage')->shouldBeCalled()->willReturn(true);
        $container->get('security.token_storage')->shouldBeCalled()->willReturn($context);

        $context->getToken()->shouldBeCalled()->willReturn($token);
        $token->getUser()->shouldBeCalled()->willReturn($user);

        $commentRepository->findByUser('comment-id', $user)->shouldBeCalled()->willReturn($comment);
        $container->get('kreta_comment.form_handler.comment')->shouldBeCalled()->willReturn($handler);
        $request->get('issue')->shouldBeCalled()->willReturn($issue);
        $handler->processForm(
            $request, $comment, ['method' => 'PUT', 'issue' => $issue]
        )->shouldBeCalled()->willReturn($comment);

        $this->putCommentsAction($request, 'issue-id', 'comment-id')->shouldReturn($comment);
    }
}
