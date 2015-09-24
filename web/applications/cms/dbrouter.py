class AntiProductsRouter(object):

    '''
    A router to all database operations on models in the promotion application.

    '''

    def db_for_read(self, model, **hints):
    
        if model._meta.app_label == 'anti_products':
        
            return 'anti_products'
        
        return None

    def db_for_write(self, model, **hints):
        
        if model._meta.app_label == 'anti_products':
         
            return 'anti_products'
        
        return None

    def allow_relation(self, obj1, obj2, **hints):
        
        if obj1._meta.app_label == 'anti_products' or\
            obj2._meta.app_label == 'anti_products':
            
            return True
        
        return None

    def allow_syncdb(self, db, model):

        '''
        Make sure the promotion app only appears on the 'promotion' db
        '''

        if db == 'anti_products':
            
            return model._meta.app_label == 'anti_products'
        else:
            return False
       
        
        return None
