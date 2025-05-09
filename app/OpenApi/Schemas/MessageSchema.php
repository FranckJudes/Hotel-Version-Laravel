<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="Message",
 *     title="Message",
 *     description="Modèle de message interne"
 * )
 */
class MessageSchema
{
    /**
     * @OA\Property(property="id", type="integer", example=1)
     */
    public $id;

    /**
     * @OA\Property(property="sender_id", type="integer", example=1)
     */
    public $sender_id;

    /**
     * @OA\Property(property="recipient_id", type="integer", example=2)
     */
    public $recipient_id;

    /**
     * @OA\Property(property="subject", type="string", example="Question sur ma réservation")
     */
    public $subject;

    /**
     * @OA\Property(property="content", type="string", example="Bonjour, j'aimerais savoir s'il est possible de modifier les dates de ma réservation...")
     */
    public $content;

    /**
     * @OA\Property(property="read", type="boolean", example=false)
     */
    public $read;

    /**
     * @OA\Property(property="parent_id", type="integer", nullable=true, example=null)
     */
    public $parent_id;

    /**
     * @OA\Property(property="deleted_by_sender", type="boolean", example=false)
     */
    public $deleted_by_sender;

    /**
     * @OA\Property(property="deleted_by_recipient", type="boolean", example=false)
     */
    public $deleted_by_recipient;

    /**
     * @OA\Property(property="created_at", type="string", format="date-time")
     */
    public $created_at;

    /**
     * @OA\Property(property="updated_at", type="string", format="date-time")
     */
    public $updated_at;

    /**
     * @OA\Property(property="sender", ref="#/components/schemas/User")
     */
    public $sender;

    /**
     * @OA\Property(property="recipient", ref="#/components/schemas/User")
     */
    public $recipient;

    /**
     * @OA\Property(property="parent", ref="#/components/schemas/Message", nullable=true)
     */
    public $parent;

    /**
     * @OA\Property(
     *     property="replies",
     *     type="array",
     *     @OA\Items(ref="#/components/schemas/Message")
     * )
     */
    public $replies;
}
