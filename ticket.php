<?php
namespace EventDispatcher;
use Freshdesk\Model\Contact as ContactM;
use Freshdesk\Model\Ticket as TicketM,
    Freshdesk\Model\Note,
    \InvalidArgumentException,
    \RuntimeException;
class Ticket extends Rest
{
    const FILTER_ALL = 'all_tickets';
    const FILTER_OPEN = 'open';
    const FILTER_HOLD = 'on_hold';
    const FILTER_OVERDUE = 'overdue';
    const FILTER_TODAY = 'due_today';
    const FILTER_NEW = 'new';
    const FILTER_SPAM = 'spam';
    const FILTER_DELETED = 'deleted';
    
   

    public function getTicketsByEmail($email)
    {
        if (!filter_var($email,\FILTER_VALIDATE_EMAIL))
            throw new InvalidArgumentException(
                sprintf(
                    '%s is not a valid email address',
                    $email
                )
            );
        $json = $this->restCall(
            $this->getTicketUrl($email),
            self::METHOD_GET
        );
        if (!$json)
            return null;
        return json_decode($json);
    }
    
    
    
   
    public function getTicketNotes(TicketM $model, $requesterOnly = true, $includePrivate = false)
    {
        $notes = $model->getNotes();
        if (empty($notes))
        {
            $model = $this->getFullTicket(
                $model->getDisplayId(),
                $model
            );
            $notes = $model->getNotes();
        }
        $return = array();
        foreach ($notes as $note)
        {
       
            if ($includePrivate === false && $note->getPrivate())
                continue;//do not include private tickets
            if ($requesterOnly === true && $note->getUserId() === $model->getRequesterId())
                $return[] = $note;
            else
                $return[] = $note;
        }
        return $return;
    }
    
   
    public function getFullTicket(TicketM $ticket)
    {
        $response = json_decode(
            $this->restCall(
                sprintf(
                    '/helpdesk/tickets/%d.json',
                    $ticket->getDisplayId()
                ),
                self::METHOD_GET
            )
        );
        return $ticket->setAll($response);
    }
  
  
  
    public function createNewTicket(TicketM $ticket)
    {
        $data = $ticket->toJsonData();
        $response = $this->restCall(
            '/helpdesk/tickets.json',
            self::METHOD_POST,
            $data
        );
        if (!$response)
            throw new RuntimeException(
                sprintf(
                    'Failed to create ticket with data: %s',
                    $data
                )
            );
        $json = json_decode(
            $response
        );
        //update ticket model, set ids and created timestamp
        return $ticket->setAll(
            $json->helpdesk_ticket
        );
    }
  
    public function updateTicket(TicketM $ticket)
    {
        $url = sprintf(
            '/helpdesk/tickets/%d.json',
            $ticket->getDisplayId()
        );
        $data = $ticket->toJsonData();
        $response = json_decode(
            $this->restCall(
                $url,
                self::METHOD_PUT,
                $data
            )
        );
        return $ticket->setAll(
            $response->ticket
        );
    }
   
   
    
   
    
   
    public function addTicket(Note $note)
    {
        $url = sprintf(
            '/tickets/%d/conversations/note.json',
            $note->getTicket()
                ->getDisplayId()
        );
        $response = json_decode(
            $this->restCall(
                $url,
                self::METHOD_POST,
                $note->toJsonData()
            )
        );
        if (!property_exists($response, 'note'))
            throw new RuntimeException(
                sprintf(
                    'Failed to add note: %s',
                    json_encode($response)
                )
            );
        //todo set properties on Note instance
        return $note->setAll($response);
    }
