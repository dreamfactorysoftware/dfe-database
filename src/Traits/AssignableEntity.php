<?php namespace DreamFactory\Enterprise\Database\Traits;

use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\ModelsModel;
use DreamFactory\Library\Utility\IfSet;

trait AssignableEntity
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type array
     */
    protected static $_assignableEntityOwnerMap = false;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    public static function boot()
    {
        if ( !( get_called_class() instanceof DeployModel ) )
        {
            throw new \RuntimeException( 'This trait may only be used by the "DeployModel" class and its descendants.' );
        }

        /** @noinspection PhpUndefinedMethodInspection */
        static::$_assignableEntityOwnerMap = OwnerTypes::getOwnerInfo( static::getAssignmentOwnerType() );
    }

    /**
     * @param int|string|DeployModel $fromId
     * @param int                    $fromType
     *
     * @return bool True if removed from servitude
     */
    public function unassignEntity( $fromId, $fromType = null )
    {
        $_info = $this->_getAssignmentOwnerInfo( $fromType );

        /** @noinspection PhpUndefinedMethodInspection */
        if ( false === ( $fromType = $fromType ?: static::getAssignmentOwnerType() ) )
        {
            return true;
        }

        $_info = IfSet::get( static::$_assignableEntityOwnerMap, $fromType, [] );

        if ( empty( $_info ) )
        {
            static::$_assignableEntityOwnerMap = OwnerTypes::getOwnerInfo( $fromType );
        }

        if ( !$fromType )
        {
            return true;
        }

        $_info = OwnerTypes::getOwnerInfo( $fromType );

        if ( null !== $fromType )
        {
            static

            $_owner = ( $fromId instanceof DeployModel ) ? $fromId : OwnerTypes::getOwner( $fromId, $fromType ?: $this->_assignableEntityOwnerType );
        }

        if ( $this->belongsToCluster( $_cluster->id ) )
        {
            return
                1 == ClusterServer::where( 'cluster_id', '=', $_cluster->id )
                    ->where( 'server_id', '=', $this->id )
                    ->delete();
        }

        return false;
    }

    /**
     * @param int|string $clusterId
     *
     * @return bool
     */
    public function addToCluster( $clusterId )
    {
        //  This will fail if $clusterId is bogus
        $this->removeFromCluster( $_cluster = $this->_getCluster( $clusterId ) );

        return 1 == ClusterServer::insert( ['cluster_id' => $_cluster->id, 'server_id' => $this->id] );
    }

    /**
     * @param int|string $clusterId
     *
     * @return Cluster
     */
    protected function _getCluster( $clusterId )
    {
        if ( null === ( $_cluster = Cluster::byNameOrId( $clusterId )->first() ) )
        {
            throw new \InvalidArgumentException( 'The cluster id "' . $clusterId . '" is invalid.' );
        }

        return $_cluster;
    }

    /**
     * @param int|string $clusterId
     *
     * @return bool True if this instance
     */
    public function belongsToCluster( $clusterId )
    {
        $_cluster = $this->_getCluster( $clusterId );

        return 0 != ClusterServer::whereRaw(
            'cluster_id = :cluster_id AND server_id = :server_id',
            [
                ':cluster_id' => $_cluster->id,
                ':server_id'  => $this->id
            ]
        )->count();
    }

    /**
     * @return string
     */
    public function getAssignableEntityOwnerType()
    {
        return $this->_assignableEntityOwnerType;
    }

    /**
     * @param string $assignableEntityOwnerType
     *
     * @return AssignableEntity
     */
    public function setAssignableEntityOwnerType( $assignableEntityOwnerType )
    {
        if ( !OwnerTypes::contains( $assignableEntityOwnerType ) )
        {
            throw new \InvalidArgumentException( 'The owner type "' . $assignableEntityOwnerType . '" is invalid.' );
        }

        $this->_assignableEntityOwnerType = $assignableEntityOwnerType;
        $this->_ownerClass = 'DreamFactory\\Enterprise\\Database\\Deploy\\' . OwnerTypes::prettyNameOf( $assignableEntityOwnerType );

        return $this;
    }

    /**
     * @param int $ownerType
     */
    protected function _getAssignmentOwnerInfo( $ownerType )
    {
        if ( null !== ( $_info = IfSet::get( static::$_assignableEntityOwnerMap, $ownerType ) ) )
        {
            static::$_assignableEntityOwnerMap[$ownerType] = OwnerTypes::getOwnerInfo( $ownerType, false );
        }
    }
}
