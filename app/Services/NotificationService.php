<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\AgentRegister;
use App\Mail\AgentRegisterWithoutOFfice;
use App\Mail\OrderCreated;
use App\Models\Agent;
use App\Models\Office;
use App\Models\Order;
use App\Models\User;
use App\Models\Panel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\{Mail, App};
use App\Models\{
    AgentEmailSettings,
    RepairOrder,
    RemovalOrder,
    DeliveryOrder,
    OfficeEmailSettings,
    InvoiceEmailHistory,
    Invoice
};
use App\Mail\{
    RepairOrderCreated,
    RemovalOrderCreated,
    DeliveryOrderCreated,
    OrderCompleted,
    CommunicationsEmail,
    PostRenewalReminder,
    UnpaidInvoice,
    ActionNeededEmail
};
use App\Services\InvoiceService;

class NotificationService
{
    public function contact(array $data): void
    {
        $recipient = env('CONTACT_EMAIL');

        if (App::environment('local')) {
            $recipient = 'eldon@ecbctech.com';
        }

        $from = env('MAIL_FROM_ADDRESS');

        $content = nl2br($data['message']);

        $html = "<b>Name:</b> {$data['name']}<br>";
        $html .= "<b>Email:</b> {$data['email']}<br>";
        $html .= "<b>Message:</b><br> {$content}";

        Mail::send([], [], function ($message) use ($html, $recipient, $from, $data) {
            $message->to($recipient)
                ->subject('PostReps Contact form')
                ->from($from)
                ->replyTo($data['email'])
                ->setBody($html, 'text/html');
        });
    }

    public function newAgentRegister($agent)
    {
        Mail::to(env('CONTACT_EMAIL'))->send(new AgentRegister($agent));
        return  true;
    }
    public function newAgentRegisterWithoutOffice($agent)
    {
        Mail::to(env('CONTACT_EMAIL'))->send(new AgentRegisterWithoutOFfice($agent));
        return  true;
    }

    public function verifyUserEmail(User $user)
    {
        event(new Registered($user));
        return true;
    }

    public function orderCreated(Order $order)
    {
        $email = new OrderCreated($order);
        //send email to contact email
        Mail::to(env('CONTACT_EMAIL'))->send($email);

        $email = new OrderCreated($order);
        $recipients = OfficeEmailSettings::where('office_id', $order->office_id)->where('order', true)->get();

        if($recipients->isEmpty()) {
            Mail::to(Office::find($order->office_id)->user->email)->send($email);
        } else {
            Mail::to($recipients)->send($email);
        }

        if ($order->agent_id) {
            $email  = new OrderCreated($order);
            $recipients = AgentEmailSettings::where('agent_id', $order->agent_id)->where('order', true)->get();

            if($recipients->isEmpty()) {
                Mail::to(Agent::find($order->agent_id)->user->email)->send($email);
                info('Sent order created email to', ['recipients' => Agent::find($order->agent->id)->user->email]);
            } else {
                Mail::to($recipients)->send($email);
                info('Sent order created email to', ['recipients' => $recipients]);
            }
        }

        return true;
    }

    public function repairOrderCreated(RepairOrder $repairOrder)
    {
        $email = new RepairOrderCreated($repairOrder);
        //send email to contact email
        Mail::to(env('CONTACT_EMAIL'))->send($email);

        $email = new RepairOrderCreated($repairOrder);
        $recipients = OfficeEmailSettings::where('office_id', $repairOrder->order->office_id)->where('order', true)->get();

        if($recipients->isEmpty()) {
            Mail::to(Office::find($repairOrder->order->office_id)->user->email)->send($email);
        } else {
            Mail::to($recipients)->send($email);
        }


        if ($repairOrder->order->agent_id) {
            $email = new RepairOrderCreated($repairOrder);
            $recipients = AgentEmailSettings::where('agent_id', $repairOrder->order->agent_id)->where('order', true)->get();

            if($recipients->isEmpty()) {
                Mail::to(Agent::find($repairOrder->order->agent_id)->user->email)->send($email);
            } else {
                Mail::to($recipients)->send($email);
            }
        }

        return true;
    }

    public function removalOrderCreated(RemovalOrder $removalOrder)
    {
        $email  = new RemovalOrderCreated($removalOrder);
        //send email to contact email
        Mail::to(env('CONTACT_EMAIL'))->send($email);

        $email  = new RemovalOrderCreated($removalOrder);
        $recipients = OfficeEmailSettings::where('office_id', $removalOrder->order->office_id)->where('order', true)->get();

        if($recipients->isEmpty()) {
            Mail::to(Office::find($removalOrder->order->office_id)->user->email)->send($email);
        } else {
            Mail::to($recipients)->send($email);
        }

        if ($removalOrder->order->agent_id) {
            $email = new RemovalOrderCreated($removalOrder);
            $recipients = AgentEmailSettings::where('agent_id', $removalOrder->order->agent_id)->where('order', true)->get();

            if($recipients->isEmpty()) {
                Mail::to(Agent::find($removalOrder->order->agent_id)->user->email)->send($email);
            } else {
                Mail::to($recipients)->send($email);
            }
        }

        return true;
    }

    public function deliveryOrderCreated(DeliveryOrder $deliveryOrder)
    {
        $email  = new DeliveryOrderCreated($deliveryOrder);
        //send email to contact email
        Mail::to(env('CONTACT_EMAIL'))->send($email);

        $email  = new DeliveryOrderCreated($deliveryOrder);
        $recipients = OfficeEmailSettings::where('office_id', $deliveryOrder->office_id)->where('order', true)->get();

        if($recipients->isEmpty()) {
            Mail::to(Office::find($deliveryOrder->office_id)->user->email)->send($email);
        } else {
            Mail::to($recipients)->send($email);
        }


        if ($deliveryOrder->agent_id) {
            $email = new DeliveryOrderCreated($deliveryOrder);
            $recipients = AgentEmailSettings::where('agent_id', $deliveryOrder->agent_id)->where('order', true)->get();

            if($recipients->isEmpty()) {
                Mail::to(Agent::find($deliveryOrder->agent_id)->user->email)->send($email);
            } else {
                Mail::to($recipients)->send($email);
            }
        }

        return true;
    }

    public function orderCompleted($order, $orderType, $orderStatus, $outOfInventory)
    {
        if ($orderType == 'install') {
            $subject = 'Post Reps Installation Notification';
            $order->title = $subject;
            $order->type = 'installation';

            $order->service_date_type = $order->desired_date_type;
            $order->service_date = $order->desired_date;

            //$order->comments = '';
            if ($orderStatus == 'incomplete') {
                $order->installer_comments .= "<br>Installation partially completed. A repair order was created to attach the missing items.<br>";
            }

            if ($outOfInventory) {
                $order->installer_comments .= "<br> The following items are out of inventory:<br>";

                $itemsOut = explode(',', $outOfInventory);
                foreach ($itemsOut as $itemOut) {
                    $order->installer_comments .= " - {$itemOut}<br>";
                }
            }

            if ($orderStatus == 'complete') {
                $order->installer_status = 'complete';
                $order->feedback_link = "install/{$order->id}/feedback";
            }
        }

        if ($orderType == 'repair') {
            $subject = 'Post Reps Repair Notification';
            $order->agent = $order->order->agent;
            $order->office = $order->order->office;
            $order->address = $order->order->address;
            $order->title = $subject;
            $order->type = 'repair';
            //$order->comments = '';

            if ($orderStatus == 'incomplete') {
                $order->installer_comments .= "<br>Repair partially completed. A new repair order was created to attach the missing items.<br>";
            }

            if ($outOfInventory) {
                $order->installer_comments .= "<br> The following items are out of inventory:<br>";

                $itemsOut = explode(',', $outOfInventory);
                foreach ($itemsOut as $itemOut) {
                    $order->installer_comments .= " - {$itemOut}<br>";
                }
            }

            if ($orderStatus == 'complete') {
                $order->installer_status = 'complete';
                $order->feedback_link = "repair/{$order->id}/feedback";
            }
        }

        if ($orderType == 'removal') {
            $subject = 'Post Reps Removal Notification';
            $order->agent = $order->order->agent;
            $order->office = $order->order->office;
            $order->address = $order->order->address;
            $order->title = $subject;
            $order->type = 'removal';


            if ($orderStatus == 'complete') {
                $order->installer_status = 'complete';
                $order->feedback_link = "removal/{$order->id}/feedback";
            }

            $order->installer_comments .= "<br>Note that any missing or damaged items at time of removal are subject to lost/damaged fees.<br>";
        }

        if ($orderType == 'delivery') {
            $subject = 'Post Reps Delivery Notification';
            $order->title = $subject;
            $order->type = 'delivery';
            //$order->comments = '';

            if ($orderStatus == 'incomplete') {
                $order->installer_comments .= "<br>Delivery partially completed.<br>";
            }

            if ($outOfInventory) {
                $order->installer_comments .= "<br> The following items were missing:<br>";

                $itemsOut = explode(',', $outOfInventory);
                foreach ($itemsOut as $panelId) {
                    $panel = Panel::find($panelId);
                    if ($panel) {
                        $order->installer_comments .= " - {$panel->panel_name}<br>";
                    }
                }
            }

            if ($orderStatus == 'complete') {
                $order->installer_status = 'complete';
                $order->feedback_link = "delivery/{$order->id}/feedback";
            }
        }

        $email  = new OrderCompleted($order, $subject);
        //send email to contact email
        Mail::to(env('CONTACT_EMAIL'))->send($email);

        $email  = new OrderCompleted($order, $subject);
        $recipients = OfficeEmailSettings::where('office_id', $order->office->id)->where('order', true)->get();

        if($recipients->isEmpty()) {
            Mail::to(Office::find($order->office->id)->user->email)->send($email);
        } else {
            Mail::to($recipients)->send($email);
        }

        if (isset($order->agent->id)) {
            $email  = new OrderCompleted($order, $subject);
            $recipients = AgentEmailSettings::where('agent_id', $order->agent->id)->where('order', true)->get();

            if($recipients->isEmpty()) {
                Mail::to(Agent::find($order->agent->id)->user->email)->send($email);
            } else {
                Mail::to($recipients)->send($email);
            }
        }

        return true;
    }

    public function sendCommunicationsEmail($data)
    {
        if ( $data["office"] == "true" ) { //send to all offices
            $recipients = Office::join('users', 'offices.user_id', 'users.id')
            ->where('offices.inactive', false)
            ->where('users.email', 'not like', "%postreps77+%")
            ->pluck('users.email');

            foreach ($recipients as $recipient) {
                //Need to re-create the mailable instance for each recipient to prevent duplicate emails
                //https://laravel.com/docs/8.x/mail
                $email = new CommunicationsEmail($data["subject"], $data["message"]);

                Mail::to($recipient)->send($email);

                //Delay a bit to avoid too many emails per second
                usleep(200);
            }

            return;
        }

        if ( isset($data["agents"][0]) && $data["agents"][0] == "true") { //send to all agents
            $recipients = Agent::join('users', 'agents.user_id', 'users.id')
            ->where('agents.inactive', false)
            ->where('users.email', 'not like', "%postreps77+%")
            ->pluck('users.email');

            foreach ($recipients as $recipient) {
                $email = new CommunicationsEmail($data["subject"], $data["message"]);

                Mail::to($recipient)->send($email);

                //Delay a bit to avoid too many emails per second
                usleep(200);
            }

            return;
        }

        if(! isset($data["agents"]) && ! isset($data["installers"])) { // if there is no agent and installers
            $email = new CommunicationsEmail($data["subject"], $data["message"]);

            $office = Office::find($data["office"]);

            Mail::to($office->user->email)->send($email);

        } else if (isset($data["agents"]) && ! isset($data["installers"])) { // if there is agents but no installers
            $recipients = Agent::join('users', 'agents.user_id', 'users.id')
            ->whereIn('agents.id', $data["agents"])
            ->where('users.email', 'not like', "%postreps77+%")
            ->pluck('users.email');

            foreach ($recipients as $recipient) {
                $email = new CommunicationsEmail($data["subject"], $data["message"]);

                Mail::to($recipient)->send($email);

                //Delay a bit to avoid too many emails per second
                usleep(200);
            }
        } else if (isset($data["installers"])) { // if there is no agent and office
            $installers = User::find($data["installers"]);

            foreach ($installers as $installer) {
                $email = new CommunicationsEmail($data["subject"], $data["message"]);

                Mail::to($installer->email)->send($email);

                //Delay a bit to avoid too many emails per second
                usleep( 200 );
            }
        }
    }

    public function sendPostRenewalReminder(
        Order $order,
        User $payer,
        float $renewalFee,
        $nextRenewalDate
    ) {
        $post = $order->post;
        $data = [
            'agent_name' => $order->agent->user->name ?? '',
            'office_name' => $order->office->user->name,
            'address' => $order->address,
            'renewal_fee' => $renewalFee,
            'post_name' => $order->post->post_name,
            'renewal_date' => $nextRenewalDate->format('m/d/Y'),
        ];

        $email = new PostRenewalReminder($data);
        if ($payer->office) {
            $recipients = OfficeEmailSettings::where('office_id', $payer->office->id)->where('accounting', true)->get();
        }
        if ($payer->agent) {
            $recipients = AgentEmailSettings::where('agent_id', $payer->agent->id)->where('accounting', true)->get();
        }

        if ($recipients->isEmpty()) {
            Mail::to($payer->email)->send($email);
        } else {
            Mail::to($recipients)->send($email);
        }
    }

    public function sendUnpaidInvoiceReminder(int $invoiceId)
    {
        $invoice = Invoice::find($invoiceId);

        $office = Office::find($invoice->office_id);
        $recipients = OfficeEmailSettings::where('office_id', $office->id)->where('accounting', true)->get();
        InvoiceEmailHistory::create([
            'invoice_id' => $invoice->id,
        ]);

        //Generate invoice pdf attachment
        $invoiceService = new InvoiceService($invoice);
        $attachment = $invoiceService->generatePdf($invoiceId);

        if ($recipients->isEmpty()) {
            Mail::to($office->user->email)
            ->send(new UnpaidInvoice($invoice, $office->user->name, $attachment));
        } else {
            Mail::to($recipients)
            ->send(new UnpaidInvoice($invoice, $office->user->name, $attachment));
        }

        if ($invoice->agent_id) {
            $agent = Agent::find($invoice->agent_id);
            $recipients = AgentEmailSettings::where('agent_id', $agent->id)->where('accounting', true)->get();
            InvoiceEmailHistory::create([
                'invoice_id' => $invoice->id,
            ]);

            if ($recipients->isEmpty()) {
                Mail::to($agent->user->email)
                ->send(new UnpaidInvoice($invoice, $agent->user->first_name, $attachment));
            } else {
                Mail::to($recipients)
                ->send(new UnpaidInvoice($invoice, $agent->user->first_name, $attachment));
            }
        }

        //Delete pdf file after sending the email
        unlink($attachment);
    }

    public function sendActionNeededEmail(string $orderType, int $orderId)
    {
        if ($orderType == 'install') {
            $order = Order::find($orderId);

            $subject = "Order $order->order_number ON HOLD - Action is Needed";
            $address = $order->address;

            $email  = new ActionNeededEmail($subject, $order->order_number, $address);
            //send email to contact email
            Mail::to(env('CONTACT_EMAIL'))->send($email);

            $email  = new ActionNeededEmail($subject, $order->order_number, $address);
            $recipients = OfficeEmailSettings::where('office_id', $order->office->id)->where('order', true)->get();

            if($recipients->isEmpty()) {
                Mail::to(Office::find($order->office->id)->user->email)->send($email);
            } else {
                Mail::to($recipients)->send($email);
            }

            if (isset($order->agent->id)) {
                $email  = new ActionNeededEmail($subject, $order->order_number, $address);
                $recipients = AgentEmailSettings::where('agent_id', $order->agent->id)->where('order', true)->get();

                if($recipients->isEmpty()) {
                    Mail::to(Agent::find($order->agent->id)->user->email)->send($email);
                    info('Sent action needed email to', ['recipients' => Agent::find($order->agent->id)->user->email]);
                } else {
                    Mail::to($recipients)->send($email);
                    info('Sent action needed email to', ['recipients' => $recipients]);
                }
            }
        }

        if ($orderType == 'repair') {
            $repairOrder = RepairOrder::find($orderId);
            $order = $repairOrder->order;

            $subject = "Order $repairOrder->order_number ON HOLD - Action is Needed";
            $address = $order->address;

            $email  = new ActionNeededEmail($subject, $repairOrder->order_number, $address);
            //send email to contact email
            Mail::to(env('CONTACT_EMAIL'))->send($email);

            $email  = new ActionNeededEmail($subject, $repairOrder->order_number, $address);
            $recipients = OfficeEmailSettings::where('office_id', $order->office->id)->where('order', true)->get();

            if($recipients->isEmpty()) {
                Mail::to(Office::find($order->office->id)->user->email)->send($email);
            } else {
                Mail::to($recipients)->send($email);
            }

            if (isset($order->agent->id)) {
                $email  = new ActionNeededEmail($subject, $repairOrder->order_number, $address);
                $recipients = AgentEmailSettings::where('agent_id', $order->agent->id)->where('order', true)->get();

                if($recipients->isEmpty()) {
                    Mail::to(Agent::find($order->agent->id)->user->email)->send($email);
                } else {
                    Mail::to($recipients)->send($email);
                }
            }
        }

        if ($orderType == 'removal') {
            $removalOrder = RemovalOrder::find($orderId);
            $order = $removalOrder->order;

            $subject = "Order $removalOrder->order_number ON HOLD - Action is Needed";
            $address = $order->address;

            $email  = new ActionNeededEmail($subject, $removalOrder->order_number, $address);
            //send email to contact email
            Mail::to(env('CONTACT_EMAIL'))->send($email);

            $email  = new ActionNeededEmail($subject, $removalOrder->order_number, $address);
            $recipients = OfficeEmailSettings::where('office_id', $order->office->id)->where('order', true)->get();

            if($recipients->isEmpty()) {
                Mail::to(Office::find($order->office->id)->user->email)->send($email);
            } else {
                Mail::to($recipients)->send($email);
            }

            if (isset($order->agent->id)) {
                $email  = new ActionNeededEmail($subject, $removalOrder->order_number, $address);
                $recipients = AgentEmailSettings::where('agent_id', $order->agent->id)->where('order', true)->get();

                if($recipients->isEmpty()) {
                    Mail::to(Agent::find($order->agent->id)->user->email)->send($email);
                } else {
                    Mail::to($recipients)->send($email);
                }
            }
        }

        if ($orderType == 'delivery') {
            $deliveryOrder = DeliveryOrder::find($orderId);

            $subject = "Order $deliveryOrder->order_number ON HOLD - Action is Needed";
            $address = $deliveryOrder->address;

            $email  = new ActionNeededEmail($subject, $deliveryOrder->order_number, $address);
            //send email to contact email
            Mail::to(env('CONTACT_EMAIL'))->send($email);

            $email  = new ActionNeededEmail($subject, $deliveryOrder->order_number, $address);
            $recipients = OfficeEmailSettings::where('office_id', $deliveryOrder->office->id)->where('order', true)->get();

            if($recipients->isEmpty()) {
                Mail::to(Office::find($deliveryOrder->office->id)->user->email)->send($email);
            } else {
                Mail::to($recipients)->send($email);
            }

            if (isset($deliveryOrder->agent->id)) {
                $email  = new ActionNeededEmail($subject, $deliveryOrder->order_number, $address);
                $recipients = AgentEmailSettings::where('agent_id', $deliveryOrder->agent->id)->where('order', true)->get();

                if($recipients->isEmpty()) {
                    Mail::to(Agent::find($deliveryOrder->agent->id)->user->email)->send($email);
                } else {
                    Mail::to($recipients)->send($email);
                }
            }
        }


        return true;
    }
}
