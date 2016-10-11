<?php

namespace Common\Services\Campaign;

/*
* The following statuses govern the life cycle of an "Opportunity Participation", and are divided into two groups
* for Advertisers and Creators.
*
* The two groups are joined to each other in a way, as there is one of each per "Opportunity Participation".
* @see campaign_opportunity_participation.creator_status and campaign_opportunity_participation.advertiser_status respectively
*/
class Statuses //was an interface....
{
    //Opportunity Statuses - Theses statuses control the execution lifetime of an "Opportunity", and override "Opportunity Participation" statuses
    const OPPORTUNITY_IN_REVIEW = 1; //Awaiting Brandme administrative review
    const OPPORTUNITY_PENDING_EXECUTION = 2; //Opportunity is approved but started_at > now
    const OPPORTUNITY_EXECUTING = 3; //Opportunity is approved and started <= now
    const OPPORTUNITY_FINISHED = 4;
    const OPPORTUNITY_PAUSED = 5;
    const OPPORTUNITY_PAUSED_NO_MONEY = 6; //There is no money left in campaign
    const OPPORTUNITY_REVIEW_REJECTED = 7; //The opportunity was rejected by an admin member


    //Advertiser Opportunity Participation Statuses
    const ADVERTISER_OPPORTUNITY_PARTICIPATION_UNINITIALIZED = 0; //opportunity has been sent to creador, pero aun no hace nada ...esta invisible la tarjeta para el anunciante.
    const ADVERTISER_OPPORTUNITY_PARTICIPATION_OPEN = 1; //ya esta negociando el creador ... in this state the participation the anunciante NEEDS TO TAKE ACTIONs
    const ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING = 2; //Opportunity is waiting CREATOR to do something
    const ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING_DISPATCH = 3; //particiapciones aprobadas pero pendinente de lanzar - this still appears in the tab of pending
    const ADVERTISER_OPPORTUNITY_PARTICIPATION_DISPATCHED = 4; //se va a la seccion de resultados de la oportunidad ... no longer in the grid
    const ADVERTISER_OPPORTUNITY_PARTICIPATION_DISPATCH_ERROR = 5; //when something went south while publishing message
    const ADVERTISER_OPPORTUNITY_PARTICIPATION_CLOSED = 6; // bid/content rechazado por el anunciante
    const ADVERTISER_OPPORTUNITY_PARTICIPATION_REJECTED = 7;//participacion rechazado por el creador - entonces se pone invisible
    const ADVERTISER_OPPORTUNITY_PARTICIPATION_EXPIRED = 8; //se vencio from either of the parties involved

    //EL CREADOR RECIBE EL MONEDERO DE SU PARTICIPACION HASTA 30 DIAS DESPUES DE QUE ESTUVO DESPACHADO EL TWEET.

    //Creator Opportunity Participation Statuses
    const CREATOR_OPPORTUNITY_PARTICIPATION_NEW_DIRECT_OFFER = 1; //se invito el creador porque el anunciante invito al creator explicitamente pero el creador todavia no ha decidido participar
    const CREATOR_OPPORTUNITY_PARTICIPATION_NEW_OPEN_BID = 2; //se invito el creador porque conicidio con la segmentacion pero el creador todavia no ha decidido participar
    //In hindsight I probably should have created different pending statuses for negotiation and content
    const CREATOR_OPPORTUNITY_PARTICIPATION_PENDING = 3; //Opportunity is waiting response from ADVERTISER to do something on bid or content
    const CREATOR_OPPORTUNITY_PARTICIPATION_ACTIONS_REQUIRED_READY_FOR_CONTENT = 4; // ya esta aprobado el bid...el creador ahora tienen que enviar contenido.ç
    const CREATOR_OPPORTUNITY_PARTICIPATION_ACTIONS_REQUIRED_IN_NEGOTIATION = 5; //This is when the creator recieves a contrarespuesta of bid or content from anunciante - its linked kind of to CREATOR_OPPORTUNITY_PARTICIPATION_PENDING
    const CREATOR_OPPORTUNITY_PARTICIPATION_PENDING_PUBLISHING = 6; // aproved content and waiting for dispatch
    const CREATOR_OPPORTUNITY_PARTICIPATION_PUBLISHING = 7; //temporary state so that it doesn't accidently get dispatched by an overlapping process
    const CREATOR_OPPORTUNITY_PARTICIPATION_PUBLISHED = 8; //cuando se despatcho
    const CREATOR_OPPORTUNITY_PARTICIPATION_PUBLISHING_ERROR = 9; //when something went south while publishing message
    const CREATOR_OPPORTUNITY_PARTICIPATION_REJECTED = 10; // rechazado por el creador en algun momento
    const CREATOR_OPPORTUNITY_PARTICIPATION_CANCELED = 11; //cuando el anunciante rechaza la participacion en algun momento o se agota el saldo de la opportunidades
    const CREATOR_OPPORTUNITY_PARTICIPATION_EXPIRED = 12; //se vencio from either of the parties involved---- NOTE solo el anunciante puede reactivar la participacion
    //const CREATOR_OPPORTUNITY_PARTICIPATION_ACTIONS_REQUIRED_DRAFT = 11;

    /**
     * A list of opportunity participation statuses which should NOT be included when calculating active campaign/opportunity costs
     *
     * @var array
     */
    public static $excluded
        = [
            //new open offers ARE NOT included in expense calculations since there has not been a formal offer accepted (or made) by the advertiser.
            //new direct offers ARE included in expense calculations since the first bid has technically been accepted accepted by the anuciante (since the anuciante MADE the offer)
            self::CREATOR_OPPORTUNITY_PARTICIPATION_NEW_OPEN_BID,
            self::CREATOR_OPPORTUNITY_PARTICIPATION_REJECTED,
            self::CREATOR_OPPORTUNITY_PARTICIPATION_CANCELED,
            self::CREATOR_OPPORTUNITY_PARTICIPATION_EXPIRED
        ];
}


